<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $order->load('items.product');

        return response()->json($order);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address'          => 'required|array',
            'shipping_address.street'   => 'required|string',
            'shipping_address.city'     => 'required|string',
            'shipping_address.postcode' => 'required|string',
            'shipping_address.country'  => 'required|string',
            'notes'                     => 'nullable|string|max:500',
        ]);

        $cart = Cart::with('items.product')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        // Validate stock for all items before placing order
        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Insufficient stock for: {$item->product->name}",
                ], 422);
            }
        }

        $order = DB::transaction(function () use ($cart, $validated, $request) {
            $totalAmount = $cart->items->sum(fn($i) => $i->quantity * $i->product->price);

            $order = Order::create([
                'user_id'          => $request->user()->id,
                'status'           => Order::STATUS_PENDING,
                'total_amount'     => $totalAmount,
                'shipping_address' => $validated['shipping_address'],
                'notes'            => $validated['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->product->price,
                    'subtotal'   => $item->quantity * $item->product->price,
                ]);

                // Decrement stock
                $item->product->decrement('stock', $item->quantity);
            }

            // Clear the cart after order is placed
            $cart->items()->delete();

            return $order;
        });

        $order->load('items.product');

        return response()->json([
            'message' => 'Order placed successfully.',
            'order'   => $order,
        ], 201);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (! $order->canBeCancelled()) {
            return response()->json([
                'message' => "Order cannot be cancelled. Current status: {$order->status}",
            ], 422);
        }

        DB::transaction(function () use ($order) {
            // Restore stock
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);
        });

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order'   => $order->fresh('items.product'),
        ]);
    }
}
