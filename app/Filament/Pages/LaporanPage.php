<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LaporanPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'POS System';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.laporan-page';


    public ?string $dateFrom = null;

    public ?string $dateTo = null;


    public Collection $topProducts;

    public Collection $categoryPerformance;

    public bool $hasData = false;


    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo   = Carbon::now()->format('Y-m-d');

        $this->form->fill([
            'dateFrom' => $this->dateFrom,
            'dateTo'   => $this->dateTo,
        ]);

        $this->loadReport();
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dateFrom')
                    ->label('Dari Tanggal')
                    ->required()
                    ->maxDate(fn () => $this->dateTo ?? today()),

                DatePicker::make('dateTo')
                    ->label('Sampai Tanggal')
                    ->required()
                    ->minDate(fn () => $this->dateFrom)
                    ->maxDate(today()),
            ])
            ->columns(2);
    }


    public function applyFilter(): void
    {
        $this->form->validate();
        $this->loadReport();
    }


    private function loadReport(): void
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to   = Carbon::parse($this->dateTo)->endOfDay();

        /** @var ReportService $service */
        $service = app(ReportService::class);

        $this->topProducts         = $service->getTopProducts($from, $to);
        $this->categoryPerformance = $service->getCategoryPerformance($from, $to);

        $this->hasData = $this->topProducts->isNotEmpty() || $this->categoryPerformance->isNotEmpty();
    }


    public function exportExcel(): void
    {
        try {
            $this->redirect(route('laporan.export.excel', [
                'from' => $this->dateFrom,
                'to'   => $this->dateTo,
            ]));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Ekspor Excel gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportPdf(): void
    {
        try {
            $this->redirect(route('laporan.export.pdf', [
                'from' => $this->dateFrom,
                'to'   => $this->dateTo,
            ]));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Ekspor PDF gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
