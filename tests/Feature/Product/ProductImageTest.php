<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Services\Product\ProductService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageTest extends ProductTestCase
{
    public function test_owner_and_admin_upload_supported_images_with_safe_relative_names(): void
    {
        Storage::fake('public');

        foreach (['jpg', 'png', 'webp'] as $index => $extension) {
            $actor = $index === 0 ? $this->createUser('owner') : $this->createUser('admin');
            $payload = $this->productPayload($actor, attributes: [
                'code' => "IMG-{$index}",
                'barcode' => "00000{$index}",
                'image' => UploadedFile::fake()->image("../../evil.{$extension}", 100, 100),
            ]);
            $this->actingAs($actor)->post(route('products.store'), $payload)->assertRedirect();
            $product = Product::query()->where('code', "IMG-{$index}")->firstOrFail();
            $this->assertStringStartsWith('products/', $product->image_path);
            $this->assertStringNotContainsString('evil', $product->image_path);
            $this->assertStringNotContainsString('..', $product->image_path);
            Storage::disk('public')->assertExists($product->image_path);
        }
    }

    public function test_dangerous_and_oversized_files_are_rejected(): void
    {
        Storage::fake('public');
        $owner = $this->createUser('owner');
        $files = [
            UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            UploadedFile::fake()->image('large.png')->size(3073),
        ];

        foreach ($files as $index => $file) {
            $this->actingAs($owner)->post(route('products.store'), $this->productPayload(
                $owner,
                attributes: [
                    'code' => "BAD-{$index}",
                    'barcode' => "BAD{$index}",
                    'image' => $file,
                ],
            ))->assertSessionHasErrors('image');
        }

        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_replacing_and_removing_image_preserves_product(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin');
        Storage::disk('public')->put('products/old.png', 'old');
        $product = Product::factory()->create(['image_path' => 'products/old.png']);
        $payload = $this->productPayload($admin, $product->category, $product->unit, [
            'code' => $product->code,
            'barcode' => $product->barcode,
            'selling_price' => $product->selling_price,
            'minimum_stock' => $product->minimum_stock,
            'image' => UploadedFile::fake()->image('new.png'),
        ]);

        $this->actingAs($admin)->put(route('products.update', $product), $payload)->assertRedirect();
        $newPath = $product->fresh()->image_path;
        Storage::disk('public')->assertMissing('products/old.png');
        Storage::disk('public')->assertExists($newPath);

        $this->actingAs($admin)->delete(route('products.image.destroy', $product))->assertRedirect();
        $this->assertNull($product->fresh()->image_path);
        $this->assertModelExists($product);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_new_image_is_cleaned_and_old_image_survives_database_failure(): void
    {
        Storage::fake('public');
        $owner = $this->createUser('owner');
        Storage::disk('public')->put('products/original.png', 'original');
        $product = Product::factory()->create(['image_path' => 'products/original.png']);
        $duplicate = Product::factory()->create();
        $service = app(ProductService::class);
        $data = $this->productPayload($owner, $product->category, $product->unit, [
            'code' => $duplicate->code,
            'barcode' => null,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'minimum_stock' => $product->minimum_stock,
        ]);

        try {
            $service->update($product, $data, $owner, UploadedFile::fake()->image('replacement.png'));
            $this->fail('Database update seharusnya gagal karena kode duplikat.');
        } catch (QueryException) {
            $this->assertSame(['products/original.png'], Storage::disk('public')->allFiles('products'));
            $this->assertSame('products/original.png', $product->fresh()->image_path);
        }
    }

    public function test_image_delete_never_removes_file_outside_products_folder(): void
    {
        Storage::fake('public');
        $owner = $this->createUser('owner');
        Storage::disk('public')->put('private/keep.txt', 'keep');
        $product = Product::factory()->create(['image_path' => 'private/keep.txt']);

        $this->actingAs($owner)->delete(route('products.image.destroy', $product))->assertRedirect();
        Storage::disk('public')->assertExists('private/keep.txt');
        $this->assertNull($product->fresh()->image_path);
    }
}
