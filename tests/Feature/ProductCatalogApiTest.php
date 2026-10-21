<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_model_listing_for_a_product_slug_with_full_image_urls_and_default_fallback(): void
    {
        $product = Product::create([
            'name' => 'Oak Doors',
            'slug' => 'oak-doors',
            'category' => 'Doors',
            'starting_price' => 1200,
            'is_active' => true,
        ]);

        ProductModel::create([
            'product_id' => $product->id,
            'name' => 'Model With Image',
            'slug' => 'model-with-image',
            'main_image' => 'model/sample.jpg',
            'is_active' => true,
        ]);

        ProductModel::create([
            'product_id' => $product->id,
            'name' => 'Model Without Image',
            'slug' => 'model-without-image',
            'main_image' => null,
            'is_active' => true,
        ]);

        $this->getJson(route('api.products.models', $product->slug))
            ->assertOk()
            ->assertJsonPath('product.slug', $product->slug)
            ->assertJsonPath('models.0.main_image_url', asset('uploads/model/sample.jpg'))
            ->assertJsonPath('models.1.main_image_url', asset('uploads/default/model.png'));
    }

    public function test_it_returns_product_details_by_model_id_with_default_image_fallback(): void
    {
        $product = Product::create([
            'name' => 'Oak Doors',
            'slug' => 'oak-doors',
            'category' => 'Doors',
            'starting_price' => 1200,
            'is_active' => true,
        ]);

        $model = ProductModel::create([
            'product_id' => $product->id,
            'name' => 'Coral',
            'slug' => 'coral',
            'short_description' => 'Modern design, timeless appeal.',
            'description' => 'Detailed description of the Coral door.',
            'starting_price' => 1500,
            'is_premium' => true,
            'main_image' => null,
            'is_active' => true,
        ]);

        $this->getJson('/api/product-details?model_id='.$model->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.name', 'Oak Doors')
            ->assertJsonPath('model.id', $model->id)
            ->assertJsonPath('model.main_image_url', asset('uploads/default/model.png'));
    }
}
