<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Suscripciones</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .filters {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filters strong {
            color: #4472C4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-refunded {
            background-color: #e2e3e5;
            color: #383d41;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .subscription-active {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .subscription-expired {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .payment-method {
            font-weight: bold;
        }
        .summary {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary h3 {
            color: #4472C4;
            margin-top: 0;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .summary-item {
            background-color: white;
            padding: 10px;
            border-radius: 3px;
            border-left: 4px solid #4472C4;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Suscripciones</h1>
        <p>Generado el {{ date('d/m/Y H:i:s') }}</p>
    </div>

    @if($startDate || $endDate)
        <div class="filters">
            <strong>Filtros aplicados:</strong>
            @if($startDate)
                Desde: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
            @endif
            @if($endDate)
                Hasta: {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            @endif
        </div>
    @endif

    @if($subscriptions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Método de Pago</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Fecha de Pago</th>
                    <th>Período</th>
                    <th>Estado Suscripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $subscription)
                    <tr>
                        <td>{{ $subscription->id }}</td>
                        <td class="payment-method">
                            @switch($subscription->payment_method)
                                @case('stripe')
                                    Tarjeta de Crédito
                                    @break
                                @case('paypal')
                                    PayPal
                                    @break
                                @case('crypto')
                                    Criptomoneda
                                    @break
                            @endswitch
                        </td>
                        <td>
                            <strong>${{ number_format($subscription->amount, 2) }}</strong>
                            {{ $subscription->currency }}
                        </td>
                        <td>
                            @switch($subscription->status)
                                @case('completed')
                                    <span class="status-completed">Completado</span>
                                    @break
                                @case('pending')
                                    <span class="status-pending">Pendiente</span>
                                    @break
                                @case('failed')
                                    <span class="status-failed">Fallido</span>
                                    @break
                                @case('refunded')
                                    <span class="status-refunded">Reembolsado</span>
                                    @break
                            @endswitch
                        </td>
                        <td>{{ $subscription->payment_date->format('d/m/Y') }}</td>
                        <td>
                            {{ $subscription->subscription_start_date->format('d/m/Y') }} -<br>
                            {{ $subscription->subscription_end_date->format('d/m/Y') }}
                        </td>
                        <td>
                            @if($subscription->isActive())
                                <span class="subscription-active">Activa</span>
                            @elseif($subscription->isExpired())
                                <span class="subscription-expired">Expirada</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <h3>Resumen</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <strong>Total de Suscripciones:</strong> {{ $subscriptions->count() }}
                </div>
                <div class="summary-item">
                    <strong>Total Pagado:</strong> ${{ number_format($subscriptions->where('status', 'completed')->sum('amount'), 2) }}
                </div>
                <div class="summary-item">
                    <strong>Pagos Completados:</strong> {{ $subscriptions->where('status', 'completed')->count() }}
                </div>
                <div class="summary-item">
                    <strong>Suscripciones Activas:</strong> {{ $subscriptions->filter(function($s) { return $s->isActive(); })->count() }}
                </div>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>No se encontraron suscripciones</h3>
            <p>No hay suscripciones que coincidan con los filtros aplicados.</p>
        </div>
    @endif

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema BullyingRDS</p>
    </div>
</body>
</html>