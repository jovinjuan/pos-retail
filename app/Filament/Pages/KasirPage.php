<?php

namespace App\Filament\Pages;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\TransactionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class KasirPage extends Page
{
    protected static ?string $navigationLabel = 'Kasir';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'POS System';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.kasir-page';

    

    /** @var array<int, array{product_id: int, product_name: string, sku: string, unit_price: float, quantity: int, subtotal: float}> */
    public array $cartItems = [];

    public string $searchQuery = '';

    public string $paymentMethod = 'cash';

    public int $amountPaid = 0;

    public int $discount = 0;

    public int $tax = 0;

    
    public int $subtotal = 0;

    public int $total = 0;

    public int $changeAmount = 0;


    public bool $showReceipt = false;

    public array $receiptData = [];


    public array $searchResults = [];


    public function updatedCartItems(): void
    {
        $this->calculateTotals();
    }

    public function updatedDiscount(): void
    {
        $this->calculateTotals();
    }

    public function updatedTax(): void
    {
        $this->calculateTotals();
    }

    public function updatedAmountPaid(): void
    {
        $this->calculateTotals();
    }

    public function updatedPaymentMethod(): void
    {
        $this->calculateTotals();
    }

    
    public function searchProduct(): void
    {
        if (trim($this->searchQuery) === '') {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = Product::active()
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchQuery . '%')
                      ->orWhere('sku', 'like', '%' . $this->searchQuery . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'sku', 'sell_price', 'stock', 'unit'])
            ->toArray();
    }


    public function addToCart(int $productId): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            return;
        }

        $existingIndex = $this->findCartIndex($productId);

        if ($existingIndex !== null) {
            $this->cartItems[$existingIndex]['quantity']++;
            $this->cartItems[$existingIndex]['subtotal'] =
                $this->cartItems[$existingIndex]['unit_price'] * $this->cartItems[$existingIndex]['quantity'];
        } else {
            $this->cartItems[] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'sku'          => $product->sku,
                'unit_price'   => (float) $product->sell_price,
                'quantity'     => 1,
                'subtotal'     => (float) $product->sell_price,
            ];
        }

        $this->calculateTotals();
    }

    
    public function updateQuantity(int $productId, int $quantity): void
    {
        $index = $this->findCartIndex($productId);

        if ($index === null) {
            return;
        }

        if ($quantity <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        $this->cartItems[$index]['quantity'] = $quantity;
        $this->cartItems[$index]['subtotal'] = $this->cartItems[$index]['unit_price'] * $quantity;

        $this->calculateTotals();
    }

    
    public function removeFromCart(int $productId): void
    {
        $this->cartItems = array_values(
            array_filter($this->cartItems, fn ($item) => $item['product_id'] !== $productId)
        );

        $this->calculateTotals();
    }

    
    public function calculateTotals(): void
    {
        $this->subtotal = (int) array_sum(array_column($this->cartItems, 'subtotal'));
        $this->total    = (int) ($this->subtotal - $this->discount + $this->tax);

        $this->changeAmount = $this->paymentMethod === 'cash'
            ? (int) max(0, $this->amountPaid - $this->total)
            : 0;
    }


    public function confirmTransaction(): void
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->body('Tambahkan produk ke keranjang terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        if ($this->paymentMethod === 'cash' && $this->amountPaid < $this->total) {
            Notification::make()
                ->title('Pembayaran kurang')
                ->body('Jumlah uang yang dibayarkan kurang dari total transaksi.')
                ->danger()
                ->send();
            return;
        }

        try {
            /** @var TransactionService $service */
            $service = app(TransactionService::class);

            $transaction = $service->createTransaction([
                'items'          => array_map(fn ($item) => [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ], $this->cartItems),
                'payment_method' => $this->paymentMethod,
                'amount_paid'    => $this->amountPaid,
                'discount'       => $this->discount,
                'tax'            => $this->tax,
            ]);

            $this->receiptData = [
                'invoice_number' => $transaction->invoice_number,
                'items'          => $this->cartItems,
                'subtotal'       => $this->subtotal,
                'discount'       => $this->discount,
                'tax'            => $this->tax,
                'total'          => $this->total,
                'payment_method' => $this->paymentMethod,
                'amount_paid'    => $this->amountPaid,
                'change_amount'  => $this->changeAmount,
                'created_at'     => $transaction->created_at->format('d/m/Y H:i'),
            ];

            $this->showReceipt = true;

        } catch (InsufficientStockException $e) {
            Notification::make()
                ->title('Stok tidak mencukupi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Transaksi gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }


    public function closeReceipt(): void
    {
        $this->cartItems     = [];
        $this->searchQuery   = '';
        $this->searchResults = [];
        $this->paymentMethod = 'cash';
        $this->amountPaid    = 0;
        $this->discount      = 0;
        $this->tax           = 0;
        $this->subtotal      = 0;
        $this->total         = 0;
        $this->changeAmount  = 0;
        $this->receiptData   = [];
        $this->showReceipt   = false;
    }

    
    private function findCartIndex(int $productId): ?int
    {
        foreach ($this->cartItems as $index => $item) {
            if ($item['product_id'] === $productId) {
                return $index;
            }
        }
        return null;
    }


    public function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
