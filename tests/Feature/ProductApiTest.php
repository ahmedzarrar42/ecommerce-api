<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user     = User::factory()->create();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [['id', 'name', 'price', 'stock', 'category']],
                     'meta' => ['total', 'per_page'],
                 ]);
    }

    public function test_can_filter_products_by_category(): void
    {
        $otherCategory = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $this->category->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['category_id' => $otherCategory->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products?category=' . $this->category->slug);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 10.00, 'is_active' => true]);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 50.00, 'is_active' => true]);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 200.00, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products?min_price=20&max_price=100');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_show_single_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'is_active'   => true,
        ]);

        $this->getJson("/api/v1/products/{$product->id}")
             ->assertOk()
             ->assertJsonFragment(['id' => $product->id]);
    }

    public function test_admin_can_create_product(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'name'        => 'Test Product',
            'price'       => 29.99,
            'stock'       => 10,
            'sku'         => 'TEST-001',
        ];

        $this->actingAs($this->user)
             ->postJson('/api/v1/products', $payload)
             ->assertCreated()
             ->assertJsonFragment(['name' => 'Test Product']);
    }

    public function test_create_product_requires_authentication(): void
    {
        $this->postJson('/api/v1/products', [])
             ->assertUnauthorized();
    }

    public function test_create_product_validates_required_fields(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/v1/products', [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['name', 'price', 'stock', 'sku', 'category_id']);
    }
}
