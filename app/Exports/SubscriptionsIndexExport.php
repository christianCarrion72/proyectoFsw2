<?php

namespace App\Exports;

use App\Models\Subscription;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class SubscriptionsIndexExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithMapping
{
    protected $userId;
    protected $startDate;
    protected $endDate;

    public function __construct($userId, $startDate = null, $endDate = null)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = Subscription::where('user_id', $this->userId)
                           ->orderBy('created_at', 'desc');

        if ($this->startDate) {
            $query->whereDate('payment_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('payment_date', '<=', $this->endDate);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Método de Pago',
            'Monto',
            'Moneda',
            'Estado',
            'Fecha de Pago',
            'Fecha de Inicio',
            'Fecha de Fin',
            'Estado de Suscripción',
            'Fecha de Registro',
            'Notas'
        ];
    }

    public function map($subscription): array
    {
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

        // Estado de suscripción
        $subscriptionStatus = '';
        if ($subscription->isActive()) {
            $subscriptionStatus = 'Activa';
        } elseif ($subscription->isExpired()) {
            $subscriptionStatus = 'Expirada';
        }

        return [
            $subscription->id,
            $paymentMethod,
            '$' . number_format($subscription->amount, 2),
            $subscription->currency,
            $status,
            $subscription->payment_date->format('d/m/Y H:i'),
            $subscription->subscription_start_date->format('d/m/Y'),
            $subscription->subscription_end_date->format('d/m/Y'),
            $subscriptionStatus,
            $subscription->created_at->format('d/m/Y H:i:s'),
            $subscription->notes ?? 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
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
            'A:K' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 18,  // Método de Pago
            'C' => 12,  // Monto
            'D' => 8,   // Moneda
            'E' => 12,  // Estado
            'F' => 16,  // Fecha de Pago
            'G' => 14,  // Fecha de Inicio
            'H' => 14,  // Fecha de Fin
            'I' => 18,  // Estado de Suscripción
            'J' => 18,  // Fecha de Registro
            'K' => 20,  // Notas
        ];
    }
}