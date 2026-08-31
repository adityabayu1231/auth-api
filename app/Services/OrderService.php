<?php

namespace App\Services;

use App\Exceptions\ProductUnavailableException;
use App\Models\Product;

class OrderService
{
    /**
     * Hitung ulang total harga dari DB — tidak pernah percaya harga dari client.
     *
     * @param array $items Format: [['product_id' => int, 'quantity' => int, 'option_ids' => array], ...]
     * @param int $cafeId Cafe tujuan order, untuk validasi ownership tiap produk.
     * @return array ['total_amount' => int, 'items' => [['product' => Product, 'quantity' => int, 'unit_price' => int, 'subtotal' => int, 'options' => [...]], ...]]
     */
    public function calculateTotal(array $items, int $cafeId): array
    {
        $breakdown = [];
        $totalAmount = 0;

        foreach ($items as $item) {
            $product = Product::with('options')->find($item['product_id']);

            if (! $product || ! $product->is_available || (int) $product->cafe_id !== $cafeId) {
                throw new ProductUnavailableException(
                    "Produk dengan id {$item['product_id']} tidak tersedia atau bukan milik cafe ini."
                );
            }

            $quantity = $item['quantity'];
            $optionIds = $item['option_ids'] ?? [];

            $selectedOptions = $product->options->whereIn('id', $optionIds);

            if (count($optionIds) !== $selectedOptions->count()) {
                throw new ProductUnavailableException(
                    "Salah satu opsi untuk produk id {$item['product_id']} tidak ditemukan."
                );
            }

            $extraPriceTotal = $selectedOptions->sum('extra_price');
            $unitPrice = $product->base_price + $extraPriceTotal;
            $subtotal = $unitPrice * $quantity;

            $totalAmount += $subtotal;

            $breakdown[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'options' => $selectedOptions->map(fn($opt) => [
                    'option_type' => $opt->option_type,
                    'option_value' => $opt->option_value,
                    'extra_price' => $opt->extra_price,
                ])->values()->all(),
            ];
        }

        return [
            'total_amount' => $totalAmount,
            'items' => $breakdown,
        ];
    }
}
