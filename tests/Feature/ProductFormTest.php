<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductDesign;
use App\Models\ProductModel;
use App\Models\ProductShade;
use App\Models\ProductSize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_size_combinations_on_product_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create([
            'name' => 'Test product',
            'slug' => 'test-product',
            'starting_price' => 10.00,
        ]);

        $model = $product->models()->create([
            'name' => 'Model A',
            'slug' => 'model-a',
        ]);

        $design = $model->designs()->create([
            'product_id' => $product->id,
            'name' => 'Design A',
            'code' => 'D-1',
        ]);

        $shade = $design->shades()->create([
            'name' => 'Oak',
            'color_hex' => '#ffffff',
        ]);

        $shade->sizes()->create([
            'width_mm' => 1200,
            'height_mm' => 1800,
        ]);

        $this->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Size combinations', false)
            ->assertSee('Show / hide', false)
            ->assertSee('<details class="mt-3 border rounded p-3 size-combinations">', false);
    }

    public function test_model_image_upload_is_saved_to_the_model_uploads_folder(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $fixturePath = __DIR__.'/../fixtures/test-model-image.png';
        if (! file_exists($fixturePath)) {
            $image = imagecreatetruecolor(80, 60);
            $bg = imagecolorallocate($image, 20, 30, 45);
            $fg = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $bg);
            imagestring($image, 5, 18, 20, 'MODEL', $fg);
            imagepng($image, $fixturePath);
            imagedestroy($image);
        }

        $file = new \Illuminate\Http\UploadedFile(
            $fixturePath,
            'test-model-image.png',
            'image/png',
            null,
            true,
        );

        $this->post(route('admin.products.store'), [
            'name' => 'Model upload test',
            'category' => 'Doors',
            'models' => [[
                'name' => 'Model A',
                'main_image' => $file,
                'designs' => [],
            ]],
        ]);

        $product = Product::query()->where('name', 'Model upload test')->firstOrFail();
        $model = $product->models()->first();

        $this->assertNotNull($model);
        $this->assertStringContainsString('model/', $model->main_image ?? '');
        $this->assertFileExists(public_path('uploads/'.$model->main_image));
    }
}
