<x-filament-panels::page>
    {{-- Filter Form --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Filter Rentang Tanggal</h2>

        <form wire:submit.prevent="applyFilter">
            {{ $this->form }}

            <div class="mt-5 py-5 flex flex-wrap gap-3">
                <button
                    type="submit"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                    Tampilkan Laporan
                </button>

                @if($hasData)
                    <button
                        type="button"
                        wire:click="exportExcel"
                        class="mt-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-table-cells class="w-4 h-4" />
                            Ekspor Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        wire:click="exportPdf"
                        class="mt-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        <span class="flex items-center gap-1">
                            <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                            Ekspor PDF
                        </span>
                    </button>
                @endif
            </div>
        </form>
    </div>

    {{-- No Data Message --}}
    @if(!$hasData)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-10 text-center">
            <x-heroicon-o-chart-bar class="mx-auto w-8 h-8 text-gray-400 dark:text-gray-500 mb-2 opacity-50" />
            <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada data untuk periode yang dipilih</p>
        </div>
    @else
        {{-- Top Products --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Produk Terlaris</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') }} –
                    {{ \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') }}
                </p>
            </div>

            @if($topProducts->isEmpty())
                <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada data untuk periode yang dipilih
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Produk</th>
                                <th class="px-6 py-3 text-right">Unit Terjual</th>
                                <th class="px-6 py-3 text-right">Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($topProducts as $i => $product)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $product->product_name }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($product->units_sold) }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">
                                        Rp {{ number_format((float) $product->total_revenue, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Category Performance --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Performa per Kategori</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') }} –
                    {{ \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') }}
                </p>
            </div>

            @if($categoryPerformance->isEmpty())
                <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada data untuk periode yang dipilih
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Kategori</th>
                                <th class="px-6 py-3 text-right">Unit Terjual</th>
                                <th class="px-6 py-3 text-right">Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($categoryPerformance as $cat)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $cat->category_name }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($cat->units_sold) }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">
                                        Rp {{ number_format((float) $cat->total_revenue, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
