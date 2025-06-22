@extends('admin.layouts.template')

@section('title', 'Mis Suscripciones')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Mis Suscripciones</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Suscripciones</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-credit-card me-1"></i>
            Historial de Suscripciones
            <span class="badge bg-info float-end">{{ $subscriptions->total() }} registros</span>
        </div>
        <div class="card-body">
            @if($subscriptions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Método de Pago</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Fecha de Pago</th>
                                <th>Período</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subscriptions as $subscription)
                                <tr>
                                    <td>{{ $subscription->id }}</td>
                                    <td>
                                        @switch($subscription->payment_method)
                                            @case('stripe')
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-credit-card"></i> Tarjeta
                                                </span>
                                                @break
                                            @case('paypal')
                                                <span class="badge bg-warning">
                                                    <i class="fab fa-paypal"></i> PayPal
                                                </span>
                                                @break
                                            @case('crypto')
                                                <span class="badge bg-success">
                                                    <i class="fab fa-bitcoin"></i> Crypto
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <strong>${{ number_format($subscription->amount, 2) }}</strong>
                                        <small class="text-muted">{{ $subscription->currency }}</small>
                                    </td>
                                    <td>
                                        @switch($subscription->status)
                                            @case('completed')
                                                <span class="badge bg-success">Completado</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Pendiente</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">Fallido</span>
                                                @break
                                            @case('refunded')
                                                <span class="badge bg-secondary">Reembolsado</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $subscription->payment_date->format('d/m/Y') }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $subscription->subscription_start_date->format('d/m/Y') }} - 
                                            {{ $subscription->subscription_end_date->format('d/m/Y') }}
                                        </small>
                                        @if($subscription->isActive())
                                            <br><span class="badge bg-success">Activa</span>
                                        @elseif($subscription->isExpired())
                                            <br><span class="badge bg-danger">Expirada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('subscriptions.show', $subscription->id) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $subscriptions->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No tienes suscripciones registradas</h5>
                    <p class="text-muted">Cuando realices un pago, aparecerá aquí tu historial de suscripciones.</p>
                    <a href="{{ route('payment.blocked') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Realizar Pago
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Resumen de suscripciones -->
    @if($subscriptions->count() > 0)
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-white-50">Total Pagado</div>
                                <div class="h5">${{ number_format($subscriptions->where('status', 'completed')->sum('amount'), 2) }}</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-white-50">Pagos Completados</div>
                                <div class="h5">{{ $subscriptions->where('status', 'completed')->count() }}</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-white-50">Método Favorito</div>
                                <div class="h6">
                                    @php
                                        $favoriteMethod = $subscriptions->groupBy('payment_method')->sortByDesc(function($group) {
                                            return $group->count();
                                        })->keys()->first();
                                    @endphp
                                    @switch($favoriteMethod)
                                        @case('stripe')
                                            Tarjeta
                                            @break
                                        @case('paypal')
                                            PayPal
                                            @break
                                        @case('crypto')
                                            Crypto
                                            @break
                                        @default
                                            N/A
                                    @endswitch
                                </div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-white-50">Última Suscripción</div>
                                <div class="h6">{{ $subscriptions->where('status', 'completed')->sortByDesc('created_at')->first()?->created_at->format('d/m/Y') ?? 'N/A' }}</div>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Auto-ocultar alertas después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endsection