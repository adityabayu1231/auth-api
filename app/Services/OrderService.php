<?php

namespace App\Services;

use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(protected WalletService $walletService) {}

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

    /**
     * Buat order baru: hitung total, validasi saldo, insert snapshot, potong wallet.
     * Semua dalam satu transaksi DB — gagal di titik mana pun akan rollback penuh.
     */
    public function create(User $user, int $cafeId, array $items, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($user, $cafeId, $items, $notes) {
            $calculation = $this->calculateTotal($items, $cafeId);

            $order = Order::create([
                'user_id' => $user->id,
                'cafe_id' => $cafeId,
                'status' => 'pending',
                'total_amount' => $calculation['total_amount'],
                'notes' => $notes,
            ]);

            foreach ($calculation['items'] as $itemData) {
                $orderItem = $order->items()->create([
                    'product_id' => $itemData['product']->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal'],
                ]);

                foreach ($itemData['options'] as $option) {
                    $orderItem->options()->create($option);
                }
            }

            // Potong saldo wallet — kalau saldo tidak cukup, exception di sini
            // otomatis rollback semua insert Order/OrderItem/OrderItemOption di atas.
            $this->walletService->pay($user, $order, $calculation['total_amount']);

            return $order->fresh('items.options');
        });
    }

    /**
     * Transisi status valid: pending -> preparing -> finished.
     * Pembatalan (-> cancelled) ditangani terpisah lewat cancel() (lihat [B]-8).
     */
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['preparing'],
        'preparing' => ['finished'],
    ];

    public function updateStatus(Order $order, string $newStatus): Order
    {
        $currentStatus = $order->status;
        $allowedNext = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowedNext, true)) {
            throw new InvalidOrderStatusTransitionException(
                "Tidak bisa mengubah status order dari '{$currentStatus}' ke '{$newStatus}'."
            );
        }

        $order->update(['status' => $newStatus]);

        return $order->fresh();
    }
}
