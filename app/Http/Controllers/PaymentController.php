<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\CardException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Configurar Stripe
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function blocked()
    {
        return view('payment.blocked');
    }

    public function processPayment(Request $request)
    {
        $user = null;
        $redirectRoute = 'welcome';
        
        // Determinar qué guard está autenticado
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $user = $admin->persona->user ?? null;
            $redirectRoute = 'admin.dashboard';
        } elseif (Auth::guard('guardia')->check()) {
            $guardia = Auth::guard('guardia')->user();
            $user = $guardia->persona->user ?? null;
            $redirectRoute = 'guardia.dashboard';
        } elseif (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $redirectRoute = 'dashboard'; // o la ruta que uses para usuarios web
        }
        
        if (!$user) {
            return redirect()->route('payment.blocked')->with('error', 'No se pudo encontrar el usuario asociado');
        }

        // Simular procesamiento de pago
        $user->update([
            'payment_status' => true,
            'last_payment_date' => now(),
            'next_payment_due' => now()->addMonth(),
        ]);

        return redirect()->route($redirectRoute)->with('success', 'Pago procesado exitosamente');
    }

    /**
     * Procesar pago con tarjeta de crédito
     */
    /**
     * Procesar pago con tarjeta usando Stripe
     */
    public function processCardPayment(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        $redirectRoute = $this->getRedirectRoute();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo encontrar el usuario asociado'
                ], 401);
            }
            return redirect()->route('payment.blocked')
                ->with('error', 'No se pudo encontrar el usuario asociado');
        }

        // Validar datos del formulario
        $request->validate([
            'payment_method_id' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        try {
            Log::info('=== INICIO PROCESO STRIPE PAYMENT ===');
            Log::info('Usuario:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'amount' => $request->amount
            ]);

            // Crear PaymentIntent con Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // Stripe usa centavos
                'currency' => 'usd',
                'payment_method' => $request->payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => route('payment.blocked'),
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'payment_type' => 'monthly_fee'
                ]
            ]);

            Log::info('PaymentIntent creado:', [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status
            ]);

            // Verificar el estado del pago
            if ($paymentIntent->status === 'succeeded') {
                // Pago exitoso
                $this->saveCardTransaction($user, $paymentIntent, $request);
                $this->updatePaymentStatus($user);
                
                Log::info('Pago con tarjeta exitoso:', [
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $request->amount
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Pago con tarjeta procesado exitosamente',
                        'payment_intent_id' => $paymentIntent->id,
                        'redirect_url' => route($redirectRoute)
                    ]);
                }
                
                return redirect()->route($redirectRoute)
                    ->with('success', 'Pago con tarjeta procesado exitosamente. ID: ' . $paymentIntent->id);
                    
            } elseif ($paymentIntent->status === 'requires_action') {
                // Requiere autenticación 3D Secure
                return response()->json([
                    'requires_action' => true,
                    'payment_intent' => [
                        'id' => $paymentIntent->id,
                        'client_secret' => $paymentIntent->client_secret
                    ]
                ]);
                
            } else {
                Log::warning('Pago con estado inesperado:', [
                    'status' => $paymentIntent->status,
                    'payment_intent_id' => $paymentIntent->id
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El pago no pudo ser procesado. Estado: ' . $paymentIntent->status
                    ], 400);
                }
                
                return redirect()->route('payment.blocked')
                    ->with('error', 'El pago no pudo ser procesado. Estado: ' . $paymentIntent->status);
            }
            
        } catch (CardException $e) {
            // Error con la tarjeta
            Log::error('Error de tarjeta Stripe:', [
                'error' => $e->getError()->message,
                'code' => $e->getError()->code
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error con la tarjeta: ' . $e->getError()->message
                ], 400);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Error con la tarjeta: ' . $e->getError()->message);
                
        } catch (RateLimitException $e) {
            Log::error('Rate limit Stripe:', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demasiadas solicitudes. Intenta nuevamente en unos minutos.'
                ], 429);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Demasiadas solicitudes. Intenta nuevamente en unos minutos.');
                
        } catch (InvalidRequestException $e) {
            Log::error('Solicitud inválida Stripe:', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud inválida. Verifica los datos ingresados.'
                ], 400);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Solicitud inválida. Verifica los datos ingresados.');
                
        } catch (AuthenticationException $e) {
            Log::error('Error de autenticación Stripe:', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de configuración del sistema de pagos.'
                ], 500);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Error de configuración del sistema de pagos.');
                
        } catch (ApiConnectionException $e) {
            Log::error('Error de conexión Stripe:', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de conexión. Intenta nuevamente.'
                ], 500);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Error de conexión. Intenta nuevamente.');
                
        } catch (ApiErrorException $e) {
            Log::error('Error API Stripe:', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error del sistema de pagos: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Error del sistema de pagos: ' . $e->getMessage());
                
        } catch (\Exception $e) {
            Log::error('Error general en processCardPayment:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el pago con tarjeta: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Error al procesar el pago con tarjeta: ' . $e->getMessage());
        }
    }

    /**
     * Guardar información de la transacción con tarjeta
     */
    private function saveCardTransaction($user, $paymentIntent, $request)
    {
        Log::info('Transacción con tarjeta exitosa', [
            'user_id' => $user->id,
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $request->amount,
            'currency' => 'USD',
            'status' => $paymentIntent->status,
            'payment_method' => $paymentIntent->payment_method,
            'created' => now()
        ]);
    }

    /**
     * Procesar pago con PayPal
     */
    public function processPayPalPayment(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        $redirectRoute = $this->getRedirectRoute();
        
        if (!$user) {
            return redirect()->route('payment.blocked')
                ->with('error', 'No se pudo encontrar el usuario asociado');
        }

        try {
            // Aquí implementarías la lógica de pago con PayPal
            // Por ejemplo, integración con PayPal SDK
            
            // Simulación de procesamiento
            $this->updatePaymentStatus($user);
            
            return redirect()->route($redirectRoute)
                ->with('success', 'Pago con PayPal procesado exitosamente. Gracias por usar PayPal.');
                
        } catch (\Exception $e) {
            return redirect()->route('payment.blocked')
                ->with('error', 'Error al procesar el pago con PayPal: ' . $e->getMessage());
        }
    }

    /**
     * Procesar pago con criptomonedas usando Web3/MetaMask
     */
    public function processCryptoPayment(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        $redirectRoute = $this->getRedirectRoute();
        
        // Log de depuración: Usuario obtenido
        Log::info('=== INICIO PROCESO CRYPTO PAYMENT ===');
        Log::info('Usuario obtenido:', [
            'user_id' => $user ? $user->id : 'null',
            'user_email' => $user ? $user->email : 'null',
            'payment_status_antes' => $user ? $user->payment_status : 'null',
            'guard' => request()->route() ? request()->route()->getAction('middleware') : 'unknown'
        ]);
        
        if (!$user) {
            Log::error('No se pudo encontrar usuario asociado');
            return redirect()->route('payment.blocked')
                ->with('error', 'No se pudo encontrar el usuario asociado');
        }
    
        // Validar datos de la transacción
        $request->validate([
            'transaction_hash' => 'required|string|min:66|max:66',
            'token_type' => 'required|in:ETH,USDT,BNB',
            'amount' => 'required|numeric|min:0',
            'wallet_address' => 'required|string|min:42|max:42'
        ]);
    
        try {
            // Log de datos de transacción
            Log::info('Datos de transacción recibidos:', [
                'transaction_hash' => $request->transaction_hash,
                'token_type' => $request->token_type,
                'amount' => $request->amount,
                'wallet_address' => $request->wallet_address
            ]);
            
            // Verificar la transacción en la blockchain
            $isValid = $this->verifyBlockchainTransaction(
                $request->transaction_hash,
                $request->token_type,
                $request->amount,
                $request->wallet_address
            );
    
            Log::info('Resultado verificación blockchain:', ['is_valid' => $isValid]);
    
            if ($isValid) {
                // Guardar información de la transacción
                $this->saveCryptoTransaction($user, $request);
                
                // Log antes de actualizar estado
                Log::info('Antes de actualizar payment_status:', [
                    'user_id' => $user->id,
                    'payment_status_actual' => $user->payment_status
                ]);
                
                // Actualizar estado de pago
                $this->updatePaymentStatus($user);
                
                // Recargar usuario desde base de datos para verificar cambios
                $user->refresh();
                
                // Log después de actualizar estado
                Log::info('Después de actualizar payment_status:', [
                    'user_id' => $user->id,
                    'payment_status_nuevo' => $user->payment_status,
                    'last_payment_date' => $user->last_payment_date,
                    'next_payment_due' => $user->next_payment_due
                ]);
                
                Log::info('Redirigiendo a ruta:', ['redirect_route' => $redirectRoute]);
                Log::info('=== FIN PROCESO CRYPTO PAYMENT EXITOSO ===');
                
                return redirect()->route($redirectRoute)
                    ->with('success', 'Pago con criptomonedas confirmado exitosamente. Hash: ' . $request->transaction_hash);
            } else {
                Log::warning('Transacción no válida');
                return redirect()->route('payment.blocked')
                    ->with('error', 'No se pudo verificar la transacción. Por favor, intenta nuevamente.');
            }
            
        } catch (\Exception $e) {
            Log::error('Error en processCryptoPayment:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('payment.blocked')
                ->with('error', 'Error al procesar el pago con criptomonedas: ' . $e->getMessage());
        }
    }

    /**
     * Verificar transacción en la blockchain usando Infura
     */
    private function verifyBlockchainTransaction($txHash, $tokenType, $amount, $walletAddress)
    {
        try {
            $infuraUrl = env('INFURA_SEPOLIA_URL', 'https://sepolia.infura.io/v3/YOUR_PROJECT_ID');
            
            // Preparar la solicitud JSON-RPC
            $data = [
                'jsonrpc' => '2.0',
                'method' => 'eth_getTransactionByHash',
                'params' => [$txHash],
                'id' => 1
            ];

            $response = $this->makeInfuraRequest($infuraUrl, $data);
            
            if (!$response || !isset($response['result'])) {
                return false;
            }

            $transaction = $response['result'];
            
            // Verificar que la transacción existe y está confirmada
            if (!$transaction || !$transaction['blockNumber']) {
                return false;
            }

            // Verificar la dirección de destino (tu wallet de recepción)
            $expectedToAddress = env('CRYPTO_WALLET_ADDRESS');
            if (strtolower($transaction['to']) !== strtolower($expectedToAddress)) {
                return false;
            }

            // Verificar el monto (convertir de Wei a Ether para ETH)
            if ($tokenType === 'ETH') {
                $valueInEth = hexdec($transaction['value']) / pow(10, 18);
                $expectedAmount = floatval($amount);
                
                // Permitir una pequeña tolerancia en el monto
                if (abs($valueInEth - $expectedAmount) > 0.001) {
                    return false;
                }
            }

            return true;
            
        } catch (\Exception $e) {
            Log::error('Error verificando transacción blockchain: ' . $e->getMessage()); // ← Ahora funcionará
            return false;
        }
    }

    /**
     * Realizar solicitud a Infura
     */
    private function makeInfuraRequest($url, $data)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        return json_decode($response, true);
    }

    /**
     * Guardar información de la transacción crypto
     */
    private function saveCryptoTransaction($user, $request)
    {
        // Aquí puedes crear una tabla para guardar las transacciones crypto
        // Por ahora, lo guardamos en logs
        Log::info('Transacción crypto exitosa', [ // ← Ahora funcionará
            'user_id' => $user->id,
            'transaction_hash' => $request->transaction_hash,
            'token_type' => $request->token_type,
            'amount' => $request->amount,
            'wallet_address' => $request->wallet_address,
            'timestamp' => now()
        ]);
    }

    /**
     * Obtener el usuario autenticado según el guard
     */
    private function getAuthenticatedUser()
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            return $admin->persona->user ?? null;
        } elseif (Auth::guard('guardia')->check()) {
            $guardia = Auth::guard('guardia')->user();
            return $guardia->persona->user ?? null;
        } elseif (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }
        
        return null;
    }

    /**
     * Obtener la ruta de redirección según el guard
     */
    private function getRedirectRoute()
    {
        if (Auth::guard('admin')->check()) {
            return 'admin.dashboard';
        } elseif (Auth::guard('guardia')->check()) {
            return 'guardia.dashboard';
        } elseif (Auth::guard('web')->check()) {
            return 'dashboard';
        }
        
        return 'welcome';
    }

    /**
     * Actualizar el estado de pago del usuario
     */
    private function updatePaymentStatus($user)
    {
        Log::info('=== INICIO updatePaymentStatus ===');
        Log::info('Usuario antes de actualizar:', [
            'id' => $user->id,
            'email' => $user->email,
            'payment_status' => $user->payment_status,
            'last_payment_date' => $user->last_payment_date,
            'next_payment_due' => $user->next_payment_due
        ]);
        
        $result = $user->update([
            'payment_status' => true,
            'last_payment_date' => now(),
            'next_payment_due' => now()->addMonth(),
        ]);
        
        Log::info('Resultado de update:', ['success' => $result]);
        
        // Verificar en base de datos directamente
        $userFromDb = \App\Models\User::find($user->id);
        Log::info('Usuario desde DB después de update:', [
            'id' => $userFromDb->id,
            'payment_status' => $userFromDb->payment_status,
            'last_payment_date' => $userFromDb->last_payment_date,
            'next_payment_due' => $userFromDb->next_payment_due
        ]);
        
        Log::info('=== FIN updatePaymentStatus ===');
    }
}
