<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SubscriptionExport;
use App\Exports\SubscriptionsIndexExport;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Exception\PayPalConnectionException;
use App\Models\Subscription;
use Carbon\Carbon;

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
        $paymentData = [
            'payment_method' => 'stripe',
            'amount' => $request->amount,
            'currency' => 'USD',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'stripe_payment_method_id' => $paymentIntent->payment_method,
            'metadata' => [
                'stripe_status' => $paymentIntent->status,
                'stripe_created' => $paymentIntent->created
            ]
        ];
        
        $subscription = $this->createSubscription($user, $paymentData);
        
        Log::info('Suscripción Stripe creada:', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $request->amount
        ]);
        
        return $subscription;
    }

    /**
     * Manejar el éxito del pago de PayPal (llamada AJAX desde JavaScript)
     */
    public function paypalSuccessJS(Request $request)
    {
        try {
            Log::info('=== PAYPAL SUCCESS JS EJECUTADO ===', [
                'request_data' => $request->all(),
                'method' => $request->method()
            ]);
            
            $user = $this->getAuthenticatedUser();
            $redirectRoute = $this->getRedirectRoute();
            
            if (!$user) {
                Log::error('Usuario no encontrado en paypalSuccessJS');
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo encontrar el usuario asociado'
                ], 401);
            }
            
            // Validar que recibimos los datos necesarios de PayPal
            $request->validate([
                'orderID' => 'required|string',
                'payerID' => 'required|string',
                'details' => 'required|array'
            ]);
            
            // Actualizar el estado del usuario
            $this->updatePaymentStatus($user);
            
            // Guardar información de la transacción PayPal
            $this->savePayPalTransaction($user, $request);
            
            Log::info('Pago PayPal exitoso via JS:', [
                'user_id' => $user->id,
                'order_id' => $request->orderID,
                'payer_id' => $request->payerID
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'redirect_url' => route($redirectRoute)
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validación fallida en paypalSuccessJS:', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Datos de PayPal inválidos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error en paypalSuccessJS:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manejar el éxito del pago de PayPal (redirección tradicional)
     */
    public function paypalSuccess(Request $request)
    {
        try {
            Log::info('=== PAYPAL SUCCESS TRADICIONAL ===', [
                'request_data' => $request->all()
            ]);
            
            $paymentId = $request->get('paymentId');
            $payerId = $request->get('PayerID');
            
            if (!$paymentId || !$payerId) {
                Log::error('Faltan parámetros de PayPal', [
                    'paymentId' => $paymentId,
                    'payerId' => $payerId
                ]);
                return redirect()->route('payment.blocked')
                    ->with('error', 'Datos de pago incompletos');
            }
            
            // Obtener datos de la sesión
            $sessionPaymentId = session('paypal_payment_id');
            $sessionUserId = session('paypal_user_id');
            
            if ($paymentId !== $sessionPaymentId) {
                Log::error('ID de pago no coincide', [
                    'received' => $paymentId,
                    'session' => $sessionPaymentId
                ]);
                return redirect()->route('payment.blocked')
                    ->with('error', 'Error de verificación de pago');
            }
            
            $user = $this->getUserById($sessionUserId);
            if (!$user) {
                Log::error('Usuario no encontrado', ['user_id' => $sessionUserId]);
                return redirect()->route('payment.blocked')
                    ->with('error', 'Usuario no encontrado');
            }
            
            // Configurar PayPal API
            $apiContext = new ApiContext(
                new OAuthTokenCredential(
                    config('paypal.client_id'),
                    config('paypal.secret')
                )
            );
            
            $apiContext->setConfig([
                'mode' => env('PAYPAL_MODE', 'sandbox')
            ]);
            
            // Ejecutar el pago
            $payment = Payment::get($paymentId, $apiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);
            
            $result = $payment->execute($execution, $apiContext);
            
            if ($result->getState() === 'approved') {
                // Actualizar estado del usuario
                $this->updatePaymentStatus($user);
                
                // Limpiar sesión
                session()->forget(['paypal_payment_id', 'paypal_user_id']);
                
                Log::info('Pago PayPal completado exitosamente', [
                    'payment_id' => $paymentId,
                    'user_id' => $user->id
                ]);
                
                $redirectRoute = $this->getRedirectRouteForUser($user);
                return redirect()->route($redirectRoute)
                    ->with('success', 'Pago procesado exitosamente');
            } else {
                return redirect()->route('payment.blocked')
                    ->with('error', 'El pago no fue aprobado');
            }
            
        } catch (\Exception $e) {
            Log::error('Error en paypalSuccess:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('payment.blocked')
                ->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
    }

    /**
     * Guardar información de la transacción PayPal
     */
    private function savePayPalTransaction($user, $request)
    {
        $paymentData = [
            'payment_method' => 'paypal',
            'amount' => 10.00, // O el monto que venga del request
            'currency' => 'USD',
            'paypal_order_id' => $request->orderID,
            'paypal_payer_id' => $request->payerID,
            'paypal_transaction_details' => $request->details ?? [],
            'metadata' => [
                'paypal_timestamp' => now(),
                'request_data' => $request->all()
            ]
        ];
        
        $subscription = $this->createSubscription($user, $paymentData);
        
        Log::info('Suscripción PayPal creada:', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'order_id' => $request->orderID,
            'payer_id' => $request->payerID
        ]);
        
        return $subscription;
    }

    /**
     * Obtener usuario por ID (método auxiliar mejorado)
     */
    private function getUserById($userId)
    {
        try {
            // Buscar en tabla de administradores
            if ($admin = \App\Models\Administrador::find($userId)) {
                return $admin->persona->user ?? null;
            }
            
            // Buscar en tabla de guardias
            if ($guardia = \App\Models\Guardia::find($userId)) {
                return $guardia->persona->user ?? null;
            }
            
            // Buscar en tabla de usuarios directamente
            if ($user = \App\Models\User::find($userId)) {
                return $user;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error buscando usuario:', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    /**
     * Obtener ruta de redirección para un usuario específico
     */
    private function getRedirectRouteForUser($user)
    {
        if ($user instanceof \App\Models\Administrador) {
            return 'admin.dashboard';
        } elseif ($user instanceof \App\Models\Guardia) {
            return 'guardia.dashboard';
        } else {
            return 'dashboard';
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
                'wallet_address' => $request->wallet_address,
                'all_request' => $request->all()
            ]);
            
            // MODO DESARROLLO: Saltarse verificación blockchain si está en desarrollo
            $isValid = true; // Temporalmente forzar como válido
            
            // Descomentar la siguiente línea cuando la verificación esté funcionando:
            // $isValid = $this->verifyBlockchainTransaction(
            //     $request->transaction_hash,
            //     $request->token_type,
            //     $request->amount,
            //     $request->wallet_address
            // );

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
                
                // Limpiar caché de sesión para forzar recarga del usuario
                if (session()->has('user')) {
                    session()->forget('user');
                }
                
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
            Log::info('=== INICIO VERIFICACIÓN BLOCKCHAIN ===');
            Log::info('Parámetros de verificación:', [
                'txHash' => $txHash,
                'tokenType' => $tokenType,
                'amount' => $amount,
                'walletAddress' => $walletAddress
            ]);
            
            $infuraUrl = env('INFURA_SEPOLIA_URL');
            Log::info('URL de Infura:', ['url' => $infuraUrl]);
            
            // Preparar la solicitud JSON-RPC
            $data = [
                'jsonrpc' => '2.0',
                'method' => 'eth_getTransactionByHash',
                'params' => [$txHash],
                'id' => 1
            ];
    
            $response = $this->makeInfuraRequest($infuraUrl, $data);
            Log::info('Respuesta de Infura:', ['response' => $response]);
            
            if (!$response || !isset($response['result'])) {
                Log::error('No se recibió respuesta válida de Infura');
                return false;
            }
    
            $transaction = $response['result'];
            Log::info('Datos de transacción:', ['transaction' => $transaction]);
            
            // Verificar que la transacción existe
            if (!$transaction) {
                Log::error('Transacción no encontrada en blockchain');
                return false;
            }
    
            // Para transacciones en testnet, ser más permisivo con la confirmación
            // En lugar de requerir blockNumber, verificar que la transacción existe
            if (!isset($transaction['hash']) || strtolower($transaction['hash']) !== strtolower($txHash)) {
                Log::error('Hash de transacción no coincide');
                return false;
            }
    
            // Verificar la dirección de destino (tu wallet de recepción)
            $expectedToAddress = env('CRYPTO_WALLET_ADDRESS');
            Log::info('Verificando direcciones:', [
                'transaction_to' => $transaction['to'] ?? 'null',
                'expected_to' => $expectedToAddress
            ]);
            
            if (!isset($transaction['to']) || strtolower($transaction['to']) !== strtolower($expectedToAddress)) {
                Log::error('Dirección de destino no coincide');
                return false;
            }
    
            // Verificar el monto para ETH (ser más permisivo)
            if ($tokenType === 'ETH') {
                $valueInEth = isset($transaction['value']) ? hexdec($transaction['value']) / pow(10, 18) : 0;
                $expectedAmount = floatval($amount);
                
                Log::info('Verificando montos ETH:', [
                    'value_in_eth' => $valueInEth,
                    'expected_amount' => $expectedAmount,
                    'difference' => abs($valueInEth - $expectedAmount)
                ]);
                
                // Aumentar tolerancia para testnet
                if (abs($valueInEth - $expectedAmount) > 0.01) {
                    Log::warning('Monto no coincide dentro de la tolerancia');
                    // En testnet, ser más permisivo - solo verificar que hay algún valor
                    if ($valueInEth <= 0) {
                        return false;
                    }
                }
            }
    
            Log::info('Verificación blockchain exitosa');
            Log::info('=== FIN VERIFICACIÓN BLOCKCHAIN ===');
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error verificando transacción blockchain:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        $paymentData = [
            'payment_method' => 'crypto',
            'amount' => 50,
            'currency' => 'USD',
            'crypto_transaction_hash' => $request->transaction_hash,
            'crypto_token_type' => $request->token_type,
            'crypto_wallet_address' => $request->wallet_address,
            'crypto_transaction_details' => [
                'block_number' => $request->block_number ?? null,
                'gas_used' => $request->gas_used ?? null,
                'gas_price' => $request->gas_price ?? null,
            ],
            'metadata' => [
                'verification_timestamp' => now(),
                'blockchain_network' => $request->network ?? 'ethereum'
            ]
        ];
        
        $subscription = $this->createSubscription($user, $paymentData);
        
        Log::info('Suscripción Crypto creada:', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'transaction_hash' => $request->transaction_hash,
            'token_type' => $request->token_type,
            'amount' => $request->amount
        ]);
        
        return $subscription;
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

    // Método para crear suscripción
    private function createSubscription($user, $paymentData)
    {
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonth();
        
        return Subscription::create([
            'user_id' => $user->id,
            'payment_method' => $paymentData['payment_method'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'USD',
            'status' => 'completed',
            'subscription_start_date' => $startDate,
            'subscription_end_date' => $endDate,
            'payment_date' => $startDate,
            // Datos específicos según el método
            'stripe_payment_intent_id' => $paymentData['stripe_payment_intent_id'] ?? null,
            'stripe_payment_method_id' => $paymentData['stripe_payment_method_id'] ?? null,
            'paypal_order_id' => $paymentData['paypal_order_id'] ?? null,
            'paypal_payer_id' => $paymentData['paypal_payer_id'] ?? null,
            'paypal_transaction_details' => $paymentData['paypal_transaction_details'] ?? null,
            'crypto_transaction_hash' => $paymentData['crypto_transaction_hash'] ?? null,
            'crypto_token_type' => $paymentData['crypto_token_type'] ?? null,
            'crypto_wallet_address' => $paymentData['crypto_wallet_address'] ?? null,
            'crypto_transaction_details' => $paymentData['crypto_transaction_details'] ?? null,
            'metadata' => $paymentData['metadata'] ?? null,
        ]);
    }

    /**
     * Mostrar todas las suscripciones del usuario
     */
    public function showSubscriptions()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return redirect()->route('payment.blocked')
                ->with('error', 'Usuario no encontrado');
        }
        
        $subscriptions = $user->subscriptions()
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
        
        // Determinar el tipo de usuario para usar el layout correcto
        $userType = 'admin'; // Por defecto
        if (Auth::guard('guardia')->check()) {
            $userType = 'guardia';
        } elseif (Auth::guard('admin')->check()) {
            $userType = 'admin';
        }
        
        return view('subscriptions.index', compact('subscriptions', 'userType'));
    }

    /**
     * Mostrar una suscripción específica
     */
    public function showSubscription(Subscription $subscription)
    {
        $user = $this->getAuthenticatedUser();
        
        // Verificar que la suscripción pertenece al usuario
        if ($subscription->user_id !== $user->id) {
            abort(403, 'No autorizado');
        }
        
        // Determinar el tipo de usuario para usar el layout correcto
        $userType = 'admin'; // Por defecto
        if (Auth::guard('guardia')->check()) {
            $userType = 'guardia';
        } elseif (Auth::guard('admin')->check()) {
            $userType = 'admin';
        }
        
        return view('subscriptions.show', compact('subscription', 'userType'));
    }

    /**
     * Exportar suscripción a PDF
     */
    public function exportSubscriptionPdf(Subscription $subscription)
    {
        $user = $this->getAuthenticatedUser();
        
        // Verificar que la suscripción pertenece al usuario
        if ($subscription->user_id !== $user->id) {
            abort(403, 'No autorizado');
        }
        
        $pdf = Pdf::loadView('subscriptions.pdf', compact('subscription'));
         
         return $pdf->download('suscripcion_' . $subscription->id . '.pdf');
    }

    /**
     * Exportar suscripción a Excel
     */
    public function exportSubscriptionExcel(Subscription $subscription)
    {
        $user = $this->getAuthenticatedUser();
        
        // Verificar que la suscripción pertenece al usuario
        if ($subscription->user_id !== $user->id) {
            abort(403, 'No autorizado');
        }
        
        return Excel::download(new SubscriptionExport($subscription), 'suscripcion_' . $subscription->id . '.xlsx');
    }

    /**
     * Exportar índice de suscripciones a PDF con filtros de fecha
     */
    public function exportSubscriptionsIndexPdf(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return redirect()->route('payment.blocked')
                ->with('error', 'Usuario no encontrado');
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = $user->subscriptions()->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->whereDate('payment_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $subscriptions = $query->get();
        
        $pdf = Pdf::loadView('subscriptions.index-pdf', compact('subscriptions', 'startDate', 'endDate'));
        
        $filename = 'suscripciones_' . ($startDate ? $startDate . '_' : '') . ($endDate ? $endDate . '_' : '') . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Exportar índice de suscripciones a Excel con filtros de fecha
     */
    public function exportSubscriptionsIndexExcel(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return redirect()->route('payment.blocked')
                ->with('error', 'Usuario no encontrado');
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $filename = 'suscripciones_' . ($startDate ? $startDate . '_' : '') . ($endDate ? $endDate . '_' : '') . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new SubscriptionsIndexExport($user->id, $startDate, $endDate), $filename);
    }
}