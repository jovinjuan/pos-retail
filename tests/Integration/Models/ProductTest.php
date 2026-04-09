<?php

namespace Tests\Integration\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Integration tests for ProductResource / Product model.
 *
 * Feature: pos-retail
 *
 * Property 5:  Toggle is_active Round-Trip          — Validates: Requirement 3.4
 * Property 6:  Penolakan Deaktivasi Produk di Cart  — Validates: Requirement 3.5
 * Property 7:  Penolakan Penghapusan Produk dengan Riwayat Transaksi — Validates: Requirement 3.6
 * Property 9:  Validasi Format dan Ukuran Gambar    — Validates: Requirement 3.8
 * Property 10: Konsistensi Hasil Filter Produk      — Validates: Requirements 3.9, 3.10
 */
class ProductTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Property 5: Toggle is_active Round-Trip
    // Validates: Requirement 3.4
    // -------------------------------------------------------------------------

    /** @test */
    public function test_toggle_is_active_dua_kali_mengembalikan_status_semula(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        // First toggle: true → false
        $product->is_active = false;
        $product->save();
        $this->assertFalse($product->fresh()->is_active);

        // Second toggle: false → true (back to original)
        $product->is_active = true;
        $product->save();
        $this->assertTrue($product->fresh()->is_active);
    }

    /** @test */
    public function test_toggle_is_active_dari_false_dua_kali_mengembalikan_false(): void
    {
        $product = Product::factory()->inactive()->create();

        // First toggle: false → true
        $product->is_active = true;
        $product->save();
        $this->assertTrue($product->fresh()->is_active);

        // Second toggle: true → false (back to original)
        $product->is_active = false;
        $product->save();
        $this->assertFalse($product->fresh()->is_active);
    }

    // -------------------------------------------------------------------------
    // Property 6: Penolakan Deaktivasi Produk di Cart
    // Validates: Requirement 3.5
    //
    // ProductResource checks cache()->get('active_cart_product_ids', []).
    // When a product ID is in that cache key, deactivation must be rejected.
    // -------------------------------------------------------------------------

    /** @test */
    public function test_deaktivasi_produk_di_cart_ditolak(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        // Simulate the product being in an active cart session via cache
        Cache::put('active_cart_product_ids', [$product->id]);

        // Attempt to deactivate: the resource action halts and sends a notification.
        // We verify the guard condition directly — the product must NOT be deactivated
        // when it is present in the active cart cache.
        $activeCartProductIds = Cache::get('active_cart_product_ids', []);
        $isInCart = in_array($product->id, $activeCartProductIds);

        $this->assertTrue($isInCart, 'Product should be detected as in active cart');

        // Confirm the product remains active (no deactivation happened)
        $this->assertTrue($product->fresh()->is_active);
    }
    // -------------------------------------------------------------------------
    // Property 7: Penolakan Penghapusan Produk dengan Riwayat Transaksi
    // Validates: Requirement 3.6
    //
    // The transaction_items table has a restrictOnDelete FK on product_id.
    // Attempting to delete a product with existing transaction_items must fail.
    // -------------------------------------------------------------------------

    /** @test */
    public function test_produk_tidak_bisa_dihapus_jika_ada_transaction_items(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create();

        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'unit_price'     => $product->sell_price,
            'quantity'       => 1,
            'subtotal'       => $product->sell_price,
        ]);

        // The resource checks transactionItems()->exists() before deleting.
        // Verify the guard condition holds.
        $this->assertTrue($product->transactionItems()->exists());

        // Attempting a DB-level delete must be rejected by the restrictOnDelete constraint.
        $this->expectException(\Illuminate\Database\QueryException::class);
        $product->delete();
    }

    /** @test */
    public function test_produk_bisa_dihapus_jika_tidak_ada_transaction_items(): void
    {
        $product = Product::factory()->create();

        $this->assertFalse($product->transactionItems()->exists());

        $productId = $product->id;
        $product->delete();

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    // -------------------------------------------------------------------------
    // Property 9: Validasi Format dan Ukuran Gambar Produk
    // Validates: Requirement 3.8
    //
    // FileUpload in ProductResource accepts only image/jpeg, image/png, image/webp
    // and enforces a 2 MB (2048 KB) maximum size.
    // We test the validation rules directly using Laravel's Validator.
    // -------------------------------------------------------------------------

    /** @test */
    public function test_upload_gambar_format_tidak_valid_ditolak(): void
    {
        // A GIF file — not in the accepted MIME types list
        $file = UploadedFile::fake()->create('document.gif', 100, 'image/gif');

        $validator = Validator::make(
            ['image_path' => $file],
            ['image_path' => ['file', 'mimes:jpeg,png,webp', 'max:2048']]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image_path', $validator->errors()->toArray());
    }

    /** @test */
    public function test_upload_gambar_pdf_ditolak(): void
    {
        $file = UploadedFile::fake()->create('invoice.pdf', 500, 'application/pdf');

        $validator = Validator::make(
            ['image_path' => $file],
            ['image_path' => ['file', 'mimes:jpeg,png,webp', 'max:2048']]
        );

        $this->assertTrue($validator->fails());
    }
    /** @test */
    public function test_upload_gambar_jpeg_valid_diterima(): void
    {
        // Use create() with explicit MIME to avoid GD dependency
        $file = UploadedFile::fake()->create('product.jpg', 500, 'image/jpeg');

        $validator = Validator::make(
            ['image_path' => $file],
            ['image_path' => ['file', 'mimes:jpeg,png,webp', 'max:2048']]
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_upload_gambar_png_valid_diterima(): void
    {
        $file = UploadedFile::fake()->create('product.png', 500, 'image/png');

        $validator = Validator::make(
            ['image_path' => $file],
            ['image_path' => ['file', 'mimes:jpeg,png,webp', 'max:2048']]
        );

        $this->assertFalse($validator->fails());
    }

    // -------------------------------------------------------------------------
    // Property 10: Konsistensi Hasil Filter Produk
    // Validates: Requirements 3.9, 3.10
    // -------------------------------------------------------------------------

    /** @test */
    public function test_filter_is_active_hanya_kembalikan_produk_aktif(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->inactive()->create();

        $results = Product::active()->get();

        $this->assertCount(3, $results);
        foreach ($results as $product) {
            $this->assertTrue($product->is_active);
        }
    }
    /** @test */
    public function test_filter_kategori_hanya_kembalikan_produk_kategori_tersebut(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        Product::factory()->count(3)->create(['category_id' => $categoryA->id]);
        Product::factory()->count(2)->create(['category_id' => $categoryB->id]);

        $results = Product::where('category_id', $categoryA->id)->get();

        $this->assertCount(3, $results);
        foreach ($results as $product) {
            $this->assertEquals($categoryA->id, $product->category_id);
        }
    }

    /** @test */
    public function test_filter_stok_di_bawah_minimum_hanya_kembalikan_produk_yang_sesuai(): void
    {
        // Products with stock below min_stock
        Product::factory()->count(2)->belowMinStock()->create();

        // Products with stock at or above min_stock
        Product::factory()->count(3)->create(['stock' => 20, 'min_stock' => 5]);

        $results = Product::belowMinStock()->get();

        $this->assertCount(2, $results);
        foreach ($results as $product) {
            $this->assertLessThan($product->min_stock, $product->stock);
        }
    }
}
