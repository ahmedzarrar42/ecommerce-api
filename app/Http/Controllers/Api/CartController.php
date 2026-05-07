<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getOrCreateCart(Request $request): Cart
    {
        return Cart::firstOrCreate(['user_id' => $request->user()->id]);
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product.category');

        return response()->json([
            'cart'        => $cart,
            'total'       => $cart->total,
            'total_items' => $cart->total_items,
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:100',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock. Available: ' . $product->stock,
            ], 422);
        }

        $cart     = $this->getOrCreateCart($request);
        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $newQty = $cartItem->quantity + $validated['quantity'];
            if ($product->stock < $newQty) {
                return response()->json([
                    'message' => 'Insufficient stock for requested quantity.',
                ], 422);
            }
            $cartItem->update(['quantity' => $newQty]);
        } else {
            $cartItem = $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $validated['quantity'],
            ]);
        }

        $cart->load('items.product');

        return response()->json([
            'message'     => 'Item added to cart.',
            'cart'        => $cart,
            'total'       => $cart->total,
            'total_items' => $cart->total_items,
        ]);
    }

    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorize('update', $cartItem->cart);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        if ($cartItem->product->stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock.',
            ], 422);
        }

        $cartItem->update($validated);
        $cartItem->cart->load('items.product');

        return response()->json([
            'message'     => 'Cart updated.',
            'cart'        => $cartItem->cart,
            'total'       => $cartItem->cart->total,
            'total_items' => $cartItem->cart->total_items,
        ]);
    }

    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->load('items.product');

        return response()->json([
            'message'     => 'Item removed from cart.',
            'cart'        => $cart,
            'total'       => $cart->total,
            'total_items' => $cart->total_items,
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json(['message' => 'Cart cleared.']);
    }
}
