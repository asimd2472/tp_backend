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
            'is_default' => true,
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
            ->assertJsonPath('models.0.is_default', true)
            ->assertJsonPath('models.1.main_image_url', asset('uploads/default/model.webp'));
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

        $this->getJson('/api/product-details?product_slug='.$product->slug.'&model_id='.$model->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.name', 'Oak Doors')
            ->assertJsonPath('model.id', $model->id)
            ->assertJsonPath('model.main_image_url', asset('uploads/default/model.webp'))
            ->assertJsonStructure([
                'success',
                'product' => ['id', 'name', 'category', 'slug', 'short_description', 'description', 'main_image_url'],
                'model' => ['id', 'name', 'slug', 'short_description', 'description', 'starting_price', 'is_premium', 'is_active', 'is_default', 'main_image_url'],
                'designs',
                'defaults' => ['design_id', 'shade_id', 'size_id'],
            ]);
    }

    public function test_product_details_includes_faqs_specifications_rating_and_review(): void
    {
        $product = Product::create([
            'name' => 'Oak Doors',
            'slug' => 'oak-doors-content',
            'category' => 'Doors',
            'faqs' => [['question' => 'Question?', 'answer' => 'Answer.']],
            'specifications' => [['question' => 'Thickness?', 'answer' => '35 mm']],
            'rating' => 4.5,
            'review' => 'Excellent finish.',
        ]);

        $model = ProductModel::create([
            'product_id' => $product->id,
            'name' => 'Default Model',
            'slug' => 'oak-doors-content-default',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->getJson('/api/product-details?product_slug='.$product->slug)
            ->assertOk()
            ->assertJsonPath('product.faqs.0.question', 'Question?')
            ->assertJsonPath('product.faqs.0.answer', 'Answer.')
            ->assertJsonPath('product.specifications.0.question', 'Thickness?')
            ->assertJsonPath('product.specifications.0.answer', '35 mm')
            ->assertJsonPath('product.rating', 4.5)
            ->assertJsonPath('product.review', 'Excellent finish.')
            ->assertJsonPath('model.id', $model->id);
    }

    public function test_it_uses_the_products_default_model_when_model_id_is_missing_or_blank(): void
    {
        $product = Product::create([
            'name' => 'Oak Doors',
            'slug' => 'oak-doors',
            'category' => 'Doors',
            'starting_price' => 1200,
            'is_active' => true,
        ]);

        $defaultModel = ProductModel::create([
            'product_id' => $product->id,
            'name' => 'Default Model',
            'slug' => 'default-model',
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductModel::create([
            'product_id' => $product->id,
            'name' => 'Other Model',
            'slug' => 'other-model',
            'is_active' => true,
        ]);

        $withoutModelId = $this->getJson('/api/product-details?product_slug='.$product->slug)
            ->assertOk()
            ->assertJsonPath('model.id', $defaultModel->id);

        $withBlankModelId = $this->getJson('/api/product-details?product_slug='.$product->slug.'&model_id=')
            ->assertOk()
            ->assertJsonPath('model.id', $defaultModel->id);

        $this->assertSame($withoutModelId->json(), $withBlankModelId->json());
    }

    public function test_it_requires_a_product_slug_for_product_details(): void
    {
        $this->getJson('/api/product-details?model_id=25')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided data is invalid.')
            ->assertJsonValidationErrors('product_slug');
    }

    public function test_it_returns_a_json_not_found_error_when_the_product_or_model_does_not_exist(): void
    {
        $product = Product::create([
            'name' => 'Oak Doors',
            'slug' => 'oak-doors',
            'category' => 'Doors',
            'starting_price' => 1200,
            'is_active' => true,
        ]);

        $this->getJson('/api/product-details?product_slug=missing-product')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Product or model not found.',
            ]);

        $this->getJson('/api/product-details?product_slug='.$product->slug.'&model_id=25')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Product or model not found.',
            ]);
    }
}
