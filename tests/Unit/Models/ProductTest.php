<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Product model scopes.
 * Property 8: Stock Alert Invariant — Validates: Requirement 3.7
 */
class ProductTest extends TestCase
{
    public function test_scope_active_aplies_where_is_active_true(): void
    {
        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())
            ->method('where')
            ->with('is_active', true)
            ->willReturnSelf();

        $product = new Product();
        $result  = $product->scopeActive($builder);

        $this->assertSame($builder, $result);
    }

    public function test_scope_below_min_stock_applies_where_column(): void
    {
        $builder = new class extends Builder {
            public array $calls = [];
            public function __construct() {}
            public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
            {
                $this->calls[] = [$first, $operator, $second];
                return $this;
            }
        };

        $product = new Product();
        $result  = $product->scopeBelowMinStock($builder);

        $this->assertSame($builder, $result);
        $this->assertSame(['stock', '<', 'min_stock'], $builder->calls[0]);
    }

    public function test_scope_active_returns_builder(): void
    {
        $builder = $this->createMock(Builder::class);
        $builder->method('where')->willReturnSelf();

        $product = new Product();
        $this->assertSame($builder, $product->scopeActive($builder));
    }

    public function test_scope_below_min_stock_returns_builder(): void
    {
        $builder = new class extends Builder {
            public function __construct() {}
            public function whereColumn($first, $operator = null, $second = null, $boolean = 'and') { return $this; }
        };

        $product = new Product();
        $this->assertSame($builder, $product->scopeBelowMinStock($builder));
    }
}
