<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create test user
        User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Seed categories
        $categories = [
            ['name' => 'Electronics',  'description' => 'Gadgets and electronic devices'],
            ['name' => 'Clothing',     'description' => 'Fashion and apparel'],
            ['name' => 'Books',        'description' => 'Books and reading materials'],
            ['name' => 'Home & Garden','description' => 'Products for home and garden'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name'        => $cat['name'],
                'slug'        => Str::slug($cat['name']),
                'description' => $cat['description'],
                'is_active'   => true,
            ]);
        }

        // Seed products
        $electronics = Category::where('slug', 'electronics')->first();
        $clothing    = Category::where('slug', 'clothing')->first();
        $books       = Category::where('slug', 'books')->first();

        $products = [
            ['category_id' => $electronics->id, 'name' => 'Wireless Headphones', 'price' => 79.99,  'stock' => 50,  'sku' => 'ELEC-001'],
            ['category_id' => $electronics->id, 'name' => 'USB-C Hub',           'price' => 34.99,  'stock' => 100, 'sku' => 'ELEC-002'],
            ['category_id' => $electronics->id, 'name' => 'Mechanical Keyboard', 'price' => 129.99, 'stock' => 30,  'sku' => 'ELEC-003'],
            ['category_id' => $clothing->id,    'name' => 'Classic T-Shirt',      'price' => 19.99,  'stock' => 200, 'sku' => 'CLTH-001'],
            ['category_id' => $clothing->id,    'name' => 'Slim Fit Jeans',       'price' => 49.99,  'stock' => 80,  'sku' => 'CLTH-002'],
            ['category_id' => $books->id,       'name' => 'Clean Code',           'price' => 34.99,  'stock' => 60,  'sku' => 'BOOK-001'],
            ['category_id' => $books->id,       'name' => 'The Pragmatic Programmer', 'price' => 39.99, 'stock' => 45, 'sku' => 'BOOK-002'],
        ];

        foreach ($products as $prod) {
            Product::create(array_merge($prod, [
                'slug'      => Str::slug($prod['name']),
                'is_active' => true,
            ]));
        }
    }
}
