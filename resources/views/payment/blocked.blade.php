<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Bloqueado - Pago Requerido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        <form action="{{ route('payment.process') }}" method="POST" class="mb-3">
            @csrf
            <button type="submit" class="btn payment-btn">
                <i class="fas fa-credit-card me-2"></i>
                Realizar Pago
            </button>
        </form>
        
        @if(Auth::guard('admin')->check())
            <form action="{{ route('payment.admin.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </button>
            </form>
        @elseif(Auth::guard('guardia')->check())
            <form action="{{ route('payment.guardia.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </button>
            </form>
        @else
            <a href="{{ route('welcome') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>
                Volver al Inicio
            </a>
        @endif
    </div>
</body>
</html>