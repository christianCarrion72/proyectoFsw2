<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detalle de Suscripción #{{ $subscription->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 20px;
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
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #4472C4;
            color: white;
            padding: 10px;
            margin: 0 0 15px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-weight: bold;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 8px;
            border: 1px solid #dee2e6;
            width: 70%;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-refunded {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .payment-method {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .payment-stripe {
            background-color: #cce5ff;
            color: #004085;
        }
        .payment-paypal {
            background-color: #fff3cd;
            color: #856404;
        }
        .payment-crypto {
            background-color: #d4edda;
            color: #155724;
        }
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        .code {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 12px;
            word-break: break-all;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detalle de Suscripción #{{ $subscription->id }}</h1>
        <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Información Principal</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">ID de Suscripción</div>
                <div class="info-value">#{{ $subscription->id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado</div>
                <div class="info-value">
                    @switch($subscription->status)
                        @case('completed')
                            <span class="status-badge status-completed">Completado</span>
                            @break
                        @case('pending')
                            <span class="status-badge status-pending">Pendiente</span>
                            @break
                        @case('failed')
                            <span class="status-badge status-failed">Fallido</span>
                            @break
                        @case('refunded')
                            <span class="status-badge status-refunded">Reembolsado</span>
                            @break
                    @endswitch
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Monto Pagado</div>
                <div class="info-value">
                    <span class="amount">${{ number_format($subscription->amount, 2) }} {{ $subscription->currency }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Pago</div>
                <div class="info-value">{{ $subscription->payment_date->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Método de Pago</div>
                <div class="info-value">
                    @switch($subscription->payment_method)
                        @case('stripe')
                            <span class="payment-method payment-stripe">Tarjeta de Crédito</span>
                            @break
                        @case('paypal')
                            <span class="payment-method payment-paypal">PayPal</span>
                            @break
                        @case('crypto')
                            <span class="payment-method payment-crypto">Criptomoneda</span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Período de Suscripción</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Fecha de Inicio</div>
                <div class="info-value">{{ $subscription->subscription_start_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Fin</div>
                <div class="info-value">{{ $subscription->subscription_end_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado de Suscripción</div>
                <div class="info-value">
                    @if($subscription->isActive())
                        <span class="status-badge status-completed">Suscripción Activa</span>
                    @elseif($subscription->isExpired())
                        <span class="status-badge status-failed">Suscripción Expirada</span>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Registro</div>
                <div class="info-value">{{ $subscription->created_at->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>
    </div>

    @if($subscription->payment_method === 'stripe')
        <div class="section">
            <div class="section-title">Detalles de Stripe</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Payment Intent ID</div>
                    <div class="info-value"><span class="code">{{ $subscription->stripe_payment_intent_id ?: 'N/A' }}</span></div>
                </div>
                @if($subscription->stripe_payment_method_id)
                    <div class="info-row">
                        <div class="info-label">Payment Method ID</div>
                        <div class="info-value"><span class="code">{{ $subscription->stripe_payment_method_id }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    @elseif($subscription->payment_method === 'paypal')
        <div class="section">
            <div class="section-title">Detalles de PayPal</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Order ID</div>
                    <div class="info-value"><span class="code">{{ $subscription->paypal_order_id ?: 'N/A' }}</span></div>
                </div>
                @if($subscription->paypal_payer_id)
                    <div class="info-row">
                        <div class="info-label">Payer ID</div>
                        <div class="info-value"><span class="code">{{ $subscription->paypal_payer_id }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    @elseif($subscription->payment_method === 'crypto')
        <div class="section">
            <div class="section-title">Detalles de Criptomoneda</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Hash de Transacción</div>
                    <div class="info-value"><span class="code">{{ $subscription->crypto_transaction_hash ?: 'N/A' }}</span></div>
                </div>
                @if($subscription->crypto_token_type)
                    <div class="info-row">
                        <div class="info-label">Tipo de Token</div>
                        <div class="info-value">{{ $subscription->crypto_token_type }}</div>
                    </div>
                @endif
                @if($subscription->crypto_wallet_address)
                    <div class="info-row">
                        <div class="info-label">Dirección de Billetera</div>
                        <div class="info-value"><span class="code">{{ $subscription->crypto_wallet_address }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($subscription->notes)
        <div class="section">
            <div class="section-title">Notas</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Observaciones</div>
                    <div class="info-value">{{ $subscription->notes }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema BullyingRDS</p>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>