<x-filament-panels::page>
    <div class="flex flex-col lg:flex-row gap-4 h-full">

        {{-- ================================================================
             LEFT PANEL — Product Search
        ================================================================ --}}
        <div class="w-full lg:w-1/2 flex flex-col gap-4">

            {{-- Search Input --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Cari Produk</h2>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model.live="searchQuery"
                        wire:keydown.enter="searchProduct"
                        placeholder="Cari nama produk atau SKU..."
                        class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    />
                    <button
                        wire:click="searchProduct"
                        type="button"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        Cari
                    </button>
                </div>
            </div>

            {{-- Search Results --}}
            @if (!empty($searchResults))
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hasil Pencarian</h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($searchResults as $product)
                            <div class="flex items-center justify-between py-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $product['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        SKU: {{ $product['sku'] }} &bull;
                                        Stok: {{ $product['stock'] }} {{ $product['unit'] ?? '' }} &bull;
                                        Rp {{ number_format($product['sell_price'], 0, ',', '.') }}
                                    </p>
                                </div>
                                <button
                                    wire:click="addToCart({{ $product['id'] }})"
                                    type="button"
                                    class="ml-3 flex-shrink-0 rounded-lg bg-success-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-success-500 focus:outline-none focus:ring-2 focus:ring-success-500"
                                >
                                    + Tambah
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif (strlen(trim($searchQuery)) > 0 && empty($searchResults))
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        Produk tidak ditemukan.
                    </p>
                </div>
            @endif

        </div>

        {{-- ================================================================
             RIGHT PANEL — Cart + Payment
        ================================================================ --}}
        <div class="w-full lg:w-1/2 flex flex-col gap-4">

            {{-- Cart Items --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 flex-1">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Keranjang</h2>

                @if (empty($cartItems))
                    <div class="flex flex-col items-center justify-center py-6 text-gray-400 dark:text-gray-600">
                        <x-heroicon-o-shopping-cart class="w-8 h-8 mb-1.5 opacity-40" />
                        <p class="text-sm">Keranjang masih kosong</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Produk</th>
                                    <th class="pb-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                                    <th class="pb-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Harga</th>
                                    <th class="pb-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Subtotal</th>
                                    <th class="pb-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($cartItems as $item)
                                    <tr>
                                        <td class="py-2 pr-2">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $item['product_name'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $item['sku'] }}</p>
                                        </td>
                                        <td class="py-2 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button
                                                    wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['quantity'] - 1 }})"
                                                    type="button"
                                                    class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center text-xs font-bold"
                                                >−</button>
                                                <span class="w-8 text-center text-sm font-medium text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                                                <button
                                                    wire:click="updateQuantity({{ $item['product_id'] }}, {{ $item['quantity'] + 1 }})"
                                                    type="button"
                                                    class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center text-xs font-bold"
                                                >+</button>
                                            </div>
                                        </td>
                                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">
                                            Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 text-right font-medium text-gray-900 dark:text-white">
                                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 text-center">
                                            <button
                                                wire:click="removeFromCart({{ $item['product_id'] }})"
                                                type="button"
                                                class="text-danger-500 hover:text-danger-700"
                                                title="Hapus"
                                            >
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Payment Section --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Pembayaran</h2>

                <div class="space-y-3">

                    {{-- Payment Method --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                        <select
                            wire:model.live="paymentMethod"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>

                    {{-- Amount Paid (cash only) --}}
                    @if ($paymentMethod === 'cash')
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Dibayar (Rp)</label>
                            <input
                                type="number"
                                wire:model="amountPaid"
                                wire:change="$refresh"
                                min="0"
                                step="1000"
                                placeholder="0"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    @endif

                    {{-- Discount & Tax --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Diskon (Rp)</label>
                            <input
                                type="number"
                                wire:model="discount"
                                wire:change="$refresh"
                                min="0"
                                step="1000"
                                placeholder="0"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pajak (Rp)</label>
                            <input
                                type="number"
                                wire:model="tax"
                                wire:change="$refresh"
                                min="0"
                                step="1000"
                                placeholder="0"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-1.5">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if ($discount > 0)
                            <div class="flex justify-between text-sm text-success-600 dark:text-success-400">
                                <span>Diskon</span>
                                <span>− Rp {{ number_format($discount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if ($tax > 0)
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Pajak</span>
                                <span>+ Rp {{ number_format($tax, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-1.5">
                            <span>Total</span>
                            <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        @if ($paymentMethod === 'cash' && $amountPaid > 0)
                            <div class="flex justify-between text-sm font-medium text-primary-600 dark:text-primary-400">
                                <span>Kembalian</span>
                                <span>Rp {{ number_format($changeAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Confirm Button --}}
                    <button
                        wire:click="confirmTransaction"
                        type="button"
                        class="w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                        @if (empty($cartItems)) disabled @endif
                    >
                        <span wire:loading.remove wire:target="confirmTransaction">Konfirmasi Transaksi</span>
                        <span wire:loading wire:target="confirmTransaction">Memproses...</span>
                    </button>

                </div>
            </div>

        </div>
    </div>

    {{-- ================================================================
         RECEIPT MODAL
    ================================================================ --}}
    @if ($showReceipt && !empty($receiptData))
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closeReceipt"></div>

            {{-- Modal --}}
            <div class="relative z-10 w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 shadow-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

                {{-- Header --}}
                <div class="bg-primary-600 px-6 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">Struk Transaksi</h2>
                        <button wire:click="closeReceipt" type="button" class="text-white/80 hover:text-white">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                    <p class="text-sm text-primary-100 mt-0.5">{{ $receiptData['invoice_number'] ?? '' }}</p>
                    <p class="text-xs text-primary-200 mt-0.5">{{ $receiptData['created_at'] ?? '' }}</p>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">

                    {{-- Items --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Item</h3>
                        <div class="space-y-1.5">
                            @foreach ($receiptData['items'] ?? [] as $item)
                                <div class="flex justify-between text-sm">
                                    <div>
                                        <span class="text-gray-900 dark:text-white">{{ $item['product_name'] }}</span>
                                        <span class="text-gray-400 ml-1">× {{ $item['quantity'] }}</span>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-1.5">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($receiptData['subtotal'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @if (($receiptData['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-sm text-success-600">
                                <span>Diskon</span>
                                <span>− Rp {{ number_format($receiptData['discount'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if (($receiptData['tax'] ?? 0) > 0)
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Pajak</span>
                                <span>+ Rp {{ number_format($receiptData['tax'], 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-1.5">
                            <span>Total</span>
                            <span>Rp {{ number_format($receiptData['total'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-1.5">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Metode Pembayaran</span>
                            <span class="font-medium text-gray-900 dark:text-white uppercase">
                                {{ $receiptData['payment_method'] ?? '' }}
                            </span>
                        </div>
                        @if (($receiptData['payment_method'] ?? '') === 'cash')
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                <span>Dibayar</span>
                                <span>Rp {{ number_format($receiptData['amount_paid'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold text-primary-600 dark:text-primary-400">
                                <span>Kembalian</span>
                                <span>Rp {{ number_format($receiptData['change_amount'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        wire:click="closeReceipt"
                        type="button"
                        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        Tutup & Transaksi Baru
                    </button>
                </div>

            </div>
        </div>
    @endif

</x-filament-panels::page>
