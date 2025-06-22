@extends('admin.layouts.template')

@section('title', 'Detalle de Suscripción #' . $subscription->id)

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Detalle de Suscripción #{{ $subscription->id }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('subscriptions.index') }}">Suscripciones</a></li>
        <li class="breadcrumb-item active">Detalle #{{ $subscription->id }}</li>
    </ol>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Información de la Suscripción
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">ID de Suscripción</h6>
                            <p class="h5">#{{ $subscription->id }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Estado</h6>
                            <p>
                                @switch($subscription->status)
                                    @case('completed')
                                        <span class="badge bg-success fs-6">Completado</span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning fs-6">Pendiente</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-danger fs-6">Fallido</span>
                                        @break
                                    @case('refunded')
                                        <span class="badge bg-secondary fs-6">Reembolsado</span>
                                        @break
                                @endswitch
                            </p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-muted">Monto Pagado</h6>
                            <p class="h4 text-success">${{ number_format($subscription->amount, 2) }} {{ $subscription->currency }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Fecha de Pago</h6>
                            <p>{{ $subscription->payment_date->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Método de Pago</h6>
                            <p>
                                @switch($subscription->payment_method)
                                    @case('stripe')
                                        <span class="badge bg-primary fs-6">
                                            <i class="fas fa-credit-card"></i> Tarjeta de Crédito
                                        </span>
                                        @break
                                    @case('paypal')
                                        <span class="badge bg-warning fs-6">
                                            <i class="fab fa-paypal"></i> PayPal
                                        </span>
                                        @break
                                    @case('crypto')
                                        <span class="badge bg-success fs-6">
                                            <i class="fab fa-bitcoin"></i> Criptomoneda
                                        </span>
                                        @break
                                @endswitch
                            </p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Período de Suscripción</h6>
                            <p>
                                <strong>Inicio:</strong> {{ $subscription->subscription_start_date->format('d/m/Y') }}<br>
                                <strong>Fin:</strong> {{ $subscription->subscription_end_date->format('d/m/Y') }}
                            </p>
                            @if($subscription->isActive())
                                <span class="badge bg-success">Suscripción Activa</span>
                            @elseif($subscription->isExpired())
                                <span class="badge bg-danger">Suscripción Expirada</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Fecha de Registro</h6>
                            <p>{{ $subscription->created_at->format('d/m/Y H:i:s') }}</p>
                            
                            @if($subscription->notes)
                                <h6 class="text-muted mt-3">Notas</h6>
                                <p class="text-muted">{{ $subscription->notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles Específicos del Método de Pago -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-receipt me-1"></i>
                    Detalles de la Transacción
                </div>
                <div class="card-body">
                    @switch($subscription->payment_method)
                        @case('stripe')
                            <h6 class="text-primary"><i class="fas fa-credit-card"></i> Detalles de Stripe</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Payment Intent ID:</strong></td>
                                        <td><code>{{ $subscription->stripe_payment_intent_id }}</code></td>
                                    </tr>
                                    @if($subscription->stripe_payment_method_id)
                                        <tr>
                                            <td><strong>Payment Method ID:</strong></td>
                                            <td><code>{{ $subscription->stripe_payment_method_id }}</code></td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            @break
                            
                        @case('paypal')
                            <h6 class="text-warning"><i class="fab fa-paypal"></i> Detalles de PayPal</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Order ID:</strong></td>
                                        <td><code>{{ $subscription->paypal_order_id }}</code></td>
                                    </tr>
                                    @if($subscription->paypal_payer_id)
                                        <tr>
                                            <td><strong>Payer ID:</strong></td>
                                            <td><code>{{ $subscription->paypal_payer_id }}</code></td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            
                            @if($subscription->paypal_transaction_details)
                                <h6 class="mt-3">Detalles de la Transacción</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0">{{ json_encode($subscription->paypal_transaction_details, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                            @break
                            
                        @case('crypto')
                            <h6 class="text-success"><i class="fab fa-bitcoin"></i> Detalles de Criptomoneda</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Hash de Transacción:</strong></td>
                                        <td>
                                            <code class="text-break">{{ $subscription->crypto_transaction_hash }}</code>
                                            @if($subscription->crypto_transaction_hash)
                                                <a href="https://sepolia.etherscan.io/tx/{{ $subscription->crypto_transaction_hash }}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-primary ms-2">
                                                    <i class="fas fa-external-link-alt"></i> Ver en Etherscan
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($subscription->crypto_token_type)
                                        <tr>
                                            <td><strong>Tipo de Token:</strong></td>
                                            <td><span class="badge bg-info">{{ $subscription->crypto_token_type }}</span></td>
                                        </tr>
                                    @endif
                                    @if($subscription->crypto_wallet_address)
                                        <tr>
                                            <td><strong>Dirección de Billetera:</strong></td>
                                            <td><code class="text-break">{{ $subscription->crypto_wallet_address }}</code></td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            
                            @if($subscription->crypto_transaction_details)
                                <h6 class="mt-3">Detalles Técnicos</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0">{{ json_encode($subscription->crypto_transaction_details, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                            @break
                    @endswitch
                </div>
            </div>
        </div>
        
        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Acciones -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cogs me-1"></i>
                    Acciones
                </div>
                <div class="card-body">
                    <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary w-100 mb-2">
                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                    </a>
                    
                    @if($subscription->status === 'completed' && $subscription->isExpired())
                        <a href="{{ route('payment.blocked') }}" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Renovar Suscripción
                        </a>
                    @endif
                </div>
            </div>
            
            <!-- Información Adicional -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info me-1"></i>
                    Información Adicional
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Creado:</strong><br>
                        {{ $subscription->created_at->format('d/m/Y H:i:s') }}
                    </small>
                    
                    @if($subscription->updated_at != $subscription->created_at)
                        <hr>
                        <small class="text-muted">
                            <strong>Última actualización:</strong><br>
                            {{ $subscription->updated_at->format('d/m/Y H:i:s') }}
                        </small>
                    @endif
                    
                    @if($subscription->metadata)
                        <hr>
                        <small class="text-muted">
                            <strong>Metadatos:</strong><br>
                            <div class="bg-light p-2 rounded mt-1">
                                <pre class="mb-0 small">{{ json_encode($subscription->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Copiar al portapapeles
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Mostrar notificación de éxito
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.textContent = 'Copiado al portapapeles';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 2000);
        });
    }
    
    // Hacer clic en códigos para copiar
    document.querySelectorAll('code').forEach(function(code) {
        code.style.cursor = 'pointer';
        code.title = 'Clic para copiar';
        code.addEventListener('click', function() {
            copyToClipboard(this.textContent);
        });
    });
</script>

<style>
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

code {
    user-select: all;
}
</style>
@endsection