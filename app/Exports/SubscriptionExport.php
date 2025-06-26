<?php

namespace App\Exports;

use App\Models\Subscription;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SubscriptionExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function array(): array
    {
        $subscription = $this->subscription;
        
        // Determinar el estado
        $status = '';
        switch ($subscription->status) {
            case 'completed':
                $status = 'Completado';
                break;
            case 'pending':
                $status = 'Pendiente';
                break;
            case 'failed':
                $status = 'Fallido';
                break;
            case 'refunded':
                $status = 'Reembolsado';
                break;
        }
        
        // Determinar el método de pago
        $paymentMethod = '';
        switch ($subscription->payment_method) {
            case 'stripe':
                $paymentMethod = 'Tarjeta de Crédito';
                break;
            case 'paypal':
                $paymentMethod = 'PayPal';
                break;
            case 'crypto':
                $paymentMethod = 'Criptomoneda';
                break;
        }
        
        // Estado de suscripción
        $subscriptionStatus = '';
        if ($subscription->isActive()) {
            $subscriptionStatus = 'Activa';
        } elseif ($subscription->isExpired()) {
            $subscriptionStatus = 'Expirada';
        }
        
        $data = [
            ['ID de Suscripción', '#' . $subscription->id],
            ['Estado', $status],
            ['Monto Pagado', '$' . number_format($subscription->amount, 2) . ' ' . $subscription->currency],
            ['Fecha de Pago', $subscription->payment_date->format('d/m/Y H:i')],
            ['Método de Pago', $paymentMethod],
            ['Fecha de Inicio', $subscription->subscription_start_date->format('d/m/Y')],
            ['Fecha de Fin', $subscription->subscription_end_date->format('d/m/Y')],
            ['Estado de Suscripción', $subscriptionStatus],
            ['Fecha de Registro', $subscription->created_at->format('d/m/Y H:i:s')],
        ];
        
        // Agregar detalles específicos del método de pago
        if ($subscription->payment_method === 'stripe') {
            $data[] = ['', ''];
            $data[] = ['DETALLES DE STRIPE', ''];
            $data[] = ['Payment Intent ID', $subscription->stripe_payment_intent_id ?: 'N/A'];
            if ($subscription->stripe_payment_method_id) {
                $data[] = ['Payment Method ID', $subscription->stripe_payment_method_id];
            }
        } elseif ($subscription->payment_method === 'paypal') {
            $data[] = ['', ''];
            $data[] = ['DETALLES DE PAYPAL', ''];
            $data[] = ['Order ID', $subscription->paypal_order_id ?: 'N/A'];
            if ($subscription->paypal_payer_id) {
                $data[] = ['Payer ID', $subscription->paypal_payer_id];
            }
        } elseif ($subscription->payment_method === 'crypto') {
            $data[] = ['', ''];
            $data[] = ['DETALLES DE CRIPTOMONEDA', ''];
            $data[] = ['Hash de Transacción', $subscription->crypto_transaction_hash ?: 'N/A'];
            if ($subscription->crypto_token_type) {
                $data[] = ['Tipo de Token', $subscription->crypto_token_type];
            }
            if ($subscription->crypto_wallet_address) {
                $data[] = ['Dirección de Billetera', $subscription->crypto_wallet_address];
            }
        }
        
        if ($subscription->notes) {
            $data[] = ['', ''];
            $data[] = ['Notas', $subscription->notes];
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'DETALLE DE SUSCRIPCIÓN #' . $this->subscription->id,
            'VALOR'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ]
            ],
            'A:A' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 40,
        ];
    }
}