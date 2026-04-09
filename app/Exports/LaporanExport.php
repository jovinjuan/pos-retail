<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $topProducts,
        private readonly Collection $categoryPerformance,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function sheets(): array
    {
        return [
            new TopProductsSheet($this->topProducts, $this->from, $this->to),
            new CategoryPerformanceSheet($this->categoryPerformance, $this->from, $this->to),
        ];
    }
}

class TopProductsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $data,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function title(): string
    {
        return 'Produk Terlaris';
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['#', 'Produk', 'Unit Terjual', 'Total Pendapatan (Rp)'];
    }

    public function map($row): array
    {
        static $i = 0;
        $i++;
        return [
            $i,
            $row->product_name,
            (int) $row->units_sold,
            number_format((float) $row->total_revenue, 2, '.', ''),
        ];
    }
}

class CategoryPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $data,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function title(): string
    {
        return 'Performa Kategori';
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Kategori', 'Unit Terjual', 'Total Pendapatan (Rp)'];
    }

    public function map($row): array
    {
        return [
            $row->category_name,
            (int) $row->units_sold,
            number_format((float) $row->total_revenue, 2, '.', ''),
        ];
    }
}
