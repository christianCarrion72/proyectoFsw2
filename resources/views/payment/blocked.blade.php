<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso Bloqueado - Pago Requerido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/web3@1.8.0/dist/web3.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
        }
        .lock-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .payment-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .payment-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
        .payment-option i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .card-option { color: #007bff; }
        .paypal-option { color: #0070ba; }
        .crypto-option { color: #f7931a; }
        .crypto-payment-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .wallet-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .transaction-status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-success { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="lock-icon">
            <i class="fas fa-lock"></i>
        </div>
        
        <h2 class="mb-4 text-danger">Acceso Bloqueado</h2>
        
        <p class="lead mb-4">
            Tu suscripción ha vencido. Para continuar usando nuestros servicios, 
            necesitas completar el pago mensual.
        </p>
        
        @php
            $user = null;
            $monthlyFee = '50.00';
            $nextPaymentDue = null;
            $persona = null;
            $errorMessage = null;
            
            try {
                if (Auth::guard('admin')->check()) {
                    $admin = Auth::guard('admin')->user();
                    $persona = $admin->persona ?? null;
                    if (!$persona) {
                        $errorMessage = 'No se encontró la persona asociada al administrador';
                    }
                } elseif (Auth::guard('guardia')->check()) {
                    $guardia = Auth::guard('guardia')->user();
                    $persona = $guardia->persona ?? null;
                    if (!$persona) {
                        $errorMessage = 'No se encontró la persona asociada al guardia';
                    }
                }
                
                if ($persona && $persona->user) {
                    $user = $persona->user;
                    $monthlyFee = $user->monthly_fee ?? '50.00';
                    $nextPaymentDue = $user->next_payment_due;
                } elseif ($persona) {
                    $errorMessage = 'No se encontró el usuario asociado a la persona';
                }
            } catch (Exception $e) {
                $errorMessage = 'Error al cargar los datos: ' . $e->getMessage();
            }
        @endphp
        
        @if($errorMessage)
            <div class="alert alert-warning mb-4">
                <strong>Advertencia:</strong> {{ $errorMessage }}. Usando valores por defecto.
            </div>
        @endif
        
        <div class="alert alert-info mb-4">
            <strong>Tarifa mensual:</strong> ${{ $monthlyFee }}
        </div>
        
        @if($nextPaymentDue)
            <p class="text-muted mb-4">
                <small>Fecha de vencimiento: {{ $nextPaymentDue->format('d/m/Y') }}</small>
            </p>
        @endif
        
        <!-- Botón para abrir modal -->
        <button type="button" class="btn payment-btn" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="fas fa-credit-card me-2"></i>
            Realizar Pago
        </button>
        
        @if(Auth::guard('admin')->check())
            <form action="{{ route('payment.admin.logout') }}" method="POST" class="d-inline mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </button>
            </form>
        @elseif(Auth::guard('guardia')->check())
            <form action="{{ route('payment.guardia.logout') }}" method="POST" class="d-inline mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </button>
            </form>
        @else
            <a href="{{ route('welcome') }}" class="btn btn-outline-secondary mt-3">
                <i class="fas fa-home me-2"></i>
                Volver al Inicio
            </a>
        @endif
    </div>

    <!-- Modal de Opciones de Pago -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="fas fa-credit-card me-2"></i>
                        Selecciona tu Método de Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Opción Tarjeta de Crédito -->
                        <div class="col-md-4">
                            <div class="payment-option card-option" onclick="showCardPayment()">
                                <i class="fas fa-credit-card"></i>
                                <h5>Tarjeta de Crédito</h5>
                                <p class="text-muted">Visa, MasterCard, American Express</p>
                                <small class="text-success">Procesamiento inmediato</small>
                            </div>
                        </div>
                        
                        <!-- Formulario de Pago con Tarjeta (inicialmente oculto) -->
                        <div id="cardPaymentForm" class="card-payment-form" style="display: none; margin-top: 20px;">
                            <h5><i class="fas fa-credit-card"></i> Pago con Tarjeta de Crédito</h5>
                            
                            <form id="stripeForm">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Monto a pagar:</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" id="cardAmount" class="form-control" value="{{ $monthlyFee }}" readonly>
                                        <span class="input-group-text">USD</span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email:</label>
                                    <input type="email" id="cardEmail" class="form-control" value="{{ $user->email }}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Información de la tarjeta:</label>
                                    <div id="card-element" class="form-control" style="height: 40px; padding: 10px;">
                                        <!-- Stripe Elements se insertará aquí -->
                                    </div>
                                    <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button id="submitCardPayment" class="btn btn-success" type="button">
                                        <i class="fas fa-credit-card"></i> Procesar Pago
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="hideCardPayment()">
                                        Cancelar
                                    </button>
                                </div>
                                
                                <div id="cardPaymentStatus" class="mt-3"></div>
                            </form>
                        </div>
                        
                        <!-- Opción PayPal -->
                        <div class="col-md-4">
                            <form action="{{ route('payment.process.paypal') }}" method="POST">
                                @csrf
                                <div class="payment-option paypal-option" onclick="this.closest('form').submit()">
                                    <i class="fab fa-paypal"></i>
                                    <h5>PayPal</h5>
                                    <p class="text-muted">Pago seguro con tu cuenta PayPal</p>
                                    <small class="text-success">Rápido y seguro</small>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Opción Criptomonedas -->
                        <div class="col-md-4">
                            <div class="payment-option crypto-option" onclick="showCryptoPayment()">
                                <i class="fab fa-bitcoin"></i>
                                <h5>Criptomonedas</h5>
                                <p class="text-muted">ETH, USDT, BNB</p>
                                <small class="text-warning">Con MetaMask</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulario de Pago Crypto (inicialmente oculto) -->
                    <div id="cryptoPaymentForm" class="crypto-payment-form" style="display: none;">
                        <h6><i class="fab fa-ethereum me-2"></i>Pago con Criptomonedas</h6>
                        
                        <div class="wallet-info">
                            <strong>Dirección de recepción:</strong><br>
                            <code id="receivingAddress">{{ env('CRYPTO_WALLET_ADDRESS', '0x742d35Cc6634C0532925a3b8D4C9db96590c6C87') }}</code>
                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyAddress()">Copiar</button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Seleccionar Token:</label>
                                <select id="tokenType" class="form-select">
                                    <option value="ETH">Ethereum (ETH)</option>
                                    <option value="USDT">Tether (USDT)</option>
                                    <option value="BNB">Binance Coin (BNB)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto a pagar:</label>
                                <input type="text" id="paymentAmount" class="form-control" value="{{ $monthlyFee }}" readonly>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button id="connectWalletBtn" class="btn btn-primary me-2" onclick="connectWallet()">
                                <i class="fab fa-ethereum me-2"></i>Conectar MetaMask
                            </button>
                            <button id="sendPaymentBtn" class="btn btn-success" onclick="sendPayment()" disabled>
                                <i class="fas fa-paper-plane me-2"></i>Enviar Pago
                            </button>
                        </div>
                        
                        <div id="transactionStatus" class="transaction-status" style="display: none;"></div>
                        
                        <!-- Formulario oculto para enviar datos al servidor -->
                        <form id="cryptoForm" action="{{ route('payment.process.crypto') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" id="transactionHash" name="transaction_hash">
                            <input type="hidden" id="tokenTypeInput" name="token_type">
                            <input type="hidden" id="amountInput" name="amount">
                            <input type="hidden" id="walletAddressInput" name="wallet_address">
                        </form>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Monto a pagar:</strong> ${{ $monthlyFee }} USD
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Variable global para el script -->
    <script>
        window.RECEIVING_ADDRESS = '{{ env('CRYPTO_WALLET_ADDRESS', '0x742d35Cc6634C0532925a3b8D4C9db96590c6C87') }}';
    </script>
    
    <!-- Script externo -->
    <script src="{{ asset('scripts/crypto-payment.js') }}"></script>
    <!-- Stripe JS -->
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Configurar Stripe con la clave pública
        window.STRIPE_KEY = '{{ env("STRIPE_KEY") }}';
    </script>
    <script src="{{ asset('scripts/stripe-payment.js') }}"></script>
    
    <style>
    .card-payment-form {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    
    #card-element {
        background: white;
    }
    </style>
</body>
</html>