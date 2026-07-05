<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Collection $products) {}

    public function collection(): Collection { return $this->products; }

    public function headings(): array
    {
        return ['Kode', 'Nama Produk', 'Kategori', 'Stok', 'Min Stok', 'Harga Beli', 'Harga Jual', 'Satuan', 'Status', 'Barcode'];
    }

    public function map($product): array
    {
        return [
            $product->code,
            $product->name,
            $product->category->name ?? '-',
            $product->stock,
            $product->min_stock,
            $product->purchase_price,
            $product->sale_price,
            $product->unit,
            $product->is_active ? 'Aktif' : 'Nonaktif',
            $product->barcode ?? '-',
        ];
    }
}
