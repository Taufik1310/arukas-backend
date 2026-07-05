<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $sales) {}

    public function collection(): Collection { return $this->sales; }

    public function headings(): array
    {
        return ['Kode', 'Kasir', 'Pelanggan', 'Telepon', 'Subtotal', 'Diskon', 'Total', 'Bayar', 'Kembali', 'Metode', 'Status', 'Tanggal'];
    }

    public function map($sale): array
    {
        return [
            $sale->code,
            $sale->user->name ?? '-',
            $sale->customer_name ?? '-',
            $sale->customer_phone ?? '-',
            $sale->subtotal,
            $sale->discount,
            $sale->total_amount,
            $sale->paid_amount,
            $sale->change_amount,
            strtoupper($sale->payment_method),
            strtoupper($sale->payment_status),
            $sale->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF3B82F6']], 'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true]],
        ];
    }
}
