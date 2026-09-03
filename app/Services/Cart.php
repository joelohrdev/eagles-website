<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping cart. Stored as `cart` => [variantId => quantity].
 */
class Cart
{
    public const string SESSION_KEY = 'cart';

    public const int MAX_QUANTITY = 10;

    public function __construct(private Session $session) {}

    /**
     * @return array<int, int>
     */
    public function items(): array
    {
        $items = $this->session->get(self::SESSION_KEY, []);

        return is_array($items) ? array_map('intval', $items) : [];
    }

    public function add(ProductVariant $variant, int $quantity = 1): void
    {
        $items = $this->items();
        $current = $items[$variant->id] ?? 0;
        $items[$variant->id] = min(self::MAX_QUANTITY, $current + max(1, $quantity));

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function update(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($variant);

            return;
        }

        $items = $this->items();
        $items[$variant->id] = min(self::MAX_QUANTITY, $quantity);

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function remove(ProductVariant $variant): void
    {
        $items = $this->items();
        unset($items[$variant->id]);

        $this->session->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) array_sum($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    /**
     * Resolved cart lines. Variants/products that are no longer active are dropped
     * (and pruned from the session).
     *
     * @return Collection<int, array{variant: ProductVariant, quantity: int, unit_price: int, line_total: int}>
     */
    public function lines(): Collection
    {
        $items = $this->items();

        if ($items === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereKey(array_keys($items))
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->is_active && $variant->product?->is_active)
            ->keyBy('id');

        $lines = collect();

        foreach ($items as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if ($variant === null) {
                unset($items[$variantId]);

                continue;
            }

            $unitPrice = $variant->price();

            $lines->push([
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ]);
        }

        $this->session->put(self::SESSION_KEY, $items);

        return $lines;
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('line_total');
    }

    /**
     * Frontend-friendly representation.
     *
     * @return array{lines: list<array<string, mixed>>, subtotal: int, count: int}
     */
    public function toArray(): array
    {
        $lines = $this->lines();

        return [
            'lines' => array_values($lines->map(fn (array $line) => [
                'variant_id' => $line['variant']->id,
                'product_name' => $line['variant']->product->name,
                'product_slug' => $line['variant']->product->slug,
                'image_thumbnail_url' => $line['variant']->product->image_thumbnail_url,
                'size' => $line['variant']->size,
                'color' => $line['variant']->color,
                'label' => $line['variant']->label(),
                'stock' => $line['variant']->stock,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'line_total' => $line['line_total'],
            ])->all()),
            'subtotal' => (int) $lines->sum('line_total'),
            'count' => (int) $lines->sum('quantity'),
        ];
    }
}
