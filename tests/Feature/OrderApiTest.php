<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $category      = Category::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 25.00,
            'stock'       => 20,
            'is_active'   => true,
        ]);
    }

    private function addItemToCart(int $quantity = 2): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => $quantity,
        ]);
    }

    public function test_user_can_place_order(): void
    {
        $this->addItemToCart(2);

        $payload = [
            'shipping_address' => [
                'street'   => 'Musterstraße 1',
                'city'     => 'Berlin',
                'postcode' => '10115',
                'country'  => 'DE',
            ],
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/orders', $payload);

        $response->assertCreated()
                 ->assertJsonFragment(['status' => 'pending']);

        // Stock should be decremented
        $this->assertEquals(18, $this->product->fresh()->stock);

        // Cart should be cleared
        $this->assertEquals(0, Cart::where('user_id', $this->user->id)->first()->items()->count());
    }

    public function test_order_total_is_calculated_correctly(): void
    {
        $this->addItemToCart(3); // 3 × 25.00 = 75.00

        $payload = [
            'shipping_address' => [
                'street' => 'Test St', 'city' => 'Munich', 'postcode' => '80331', 'country' => 'DE',
            ],
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/orders', $payload);

        $response->assertCreated()
                 ->assertJsonFragment(['total_amount' => '75.00']);
    }

    public function test_cannot_order_with_empty_cart(): void
    {
        $payload = [
            'shipping_address' => [
                'street' => 'Test St', 'city' => 'Berlin', 'postcode' => '10115', 'country' => 'DE',
            ],
        ];

        $this->actingAs($this->user)
             ->postJson('/api/v1/orders', $payload)
             ->assertUnprocessable()
             ->assertJsonFragment(['message' => 'Your cart is empty.']);
    }

    public function test_user_can_cancel_pending_order(): void
    {
        $this->addItemToCart(2);

        $order = Order::create([
            'user_id'          => $this->user->id,
            'status'           => Order::STATUS_PENDING,
            'total_amount'     => 50.00,
            'shipping_address' => ['street' => 'Test', 'city' => 'Berlin', 'postcode' => '10115', 'country' => 'DE'],
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 2,
            'unit_price' => 25.00,
            'subtotal'   => 50.00,
        ]);

        $this->product->update(['stock' => 18]);

        $this->actingAs($this->user)
             ->postJson("/api/v1/orders/{$order->id}/cancel")
             ->assertOk()
             ->assertJsonFragment(['status' => 'cancelled']);

        // Stock should be restored
        $this->assertEquals(20, $this->product->fresh()->stock);
    }

    public function test_cannot_cancel_delivered_order(): void
    {
        $order = Order::create([
            'user_id'          => $this->user->id,
            'status'           => Order::STATUS_DELIVERED,
            'total_amount'     => 50.00,
            'shipping_address' => ['street' => 'Test', 'city' => 'Berlin', 'postcode' => '10115', 'country' => 'DE'],
        ]);

        $this->actingAs($this->user)
             ->postJson("/api/v1/orders/{$order->id}/cancel")
             ->assertUnprocessable();
    }
}
