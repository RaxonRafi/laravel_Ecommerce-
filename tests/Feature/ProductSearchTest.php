<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private int $categoryId;

    private int $otherCategoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryId = (int) DB::table('categories')->insertGetId([
            'category_name' => 'Apparel', 'created_by' => 1, 'category_photo' => 'x.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->otherCategoryId = (int) DB::table('categories')->insertGetId([
            'category_name' => 'Footwear', 'created_by' => 1, 'category_photo' => 'x.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $subcategoryId = DB::table('subcategories')->insertGetId([
            'category_id' => $this->categoryId, 'subcategory_name' => 'Shirts', 'added_by' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 15 products in Apparel — more than one page of 12.
        foreach (range(1, 15) as $i) {
            DB::table('products')->insert([
                'product_name' => "Apparel Item {$i}",
                'regular_price' => 500, 'discounted_price' => 400,
                'slug' => "apparel-item-{$i}",
                'short_description' => 'a comfortable garment',
                'sku' => 'AP-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'category_id' => $this->categoryId, 'subcategory_id' => $subcategoryId,
                'long_description' => 'd', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('products')->insert([
            'product_name' => 'Running Sneaker',
            'regular_price' => 900, 'discounted_price' => 800,
            'slug' => 'running-sneaker',
            'short_description' => 'lightweight trainers for the road',
            'sku' => 'FW-001',
            'category_id' => $this->otherCategoryId, 'subcategory_id' => $subcategoryId,
            'long_description' => 'd', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_shop_page_paginates_at_twelve_per_page(): void
    {
        $response = $this->get(route('shop'));

        $response->assertOk();
        $this->assertCount(12, $response->viewData('products')->items());
        $this->assertSame(16, $response->viewData('products')->total());
    }

    public function test_the_second_page_returns_the_remainder(): void
    {
        $response = $this->get(route('shop', ['page' => 2]));

        $response->assertOk();
        $this->assertCount(4, $response->viewData('products')->items());
    }

    public function test_it_searches_by_product_name(): void
    {
        $response = $this->get(route('shop', ['q' => 'Sneaker']));

        $products = $response->assertOk()->viewData('products');

        $this->assertSame(1, $products->total());
        $this->assertSame('Running Sneaker', $products->first()->product_name);
    }

    public function test_it_searches_by_sku(): void
    {
        $products = $this->get(route('shop', ['q' => 'FW-001']))->viewData('products');

        $this->assertSame(1, $products->total());
        $this->assertSame('FW-001', $products->first()->sku);
    }

    public function test_it_searches_by_description(): void
    {
        $products = $this->get(route('shop', ['q' => 'trainers']))->viewData('products');

        $this->assertSame(1, $products->total());
    }

    public function test_it_returns_nothing_for_an_unmatched_term(): void
    {
        $response = $this->get(route('shop', ['q' => 'nothingmatchesthis']));

        $response->assertOk()->assertSee('No products matched your search.', false);
        $this->assertSame(0, $response->viewData('products')->total());
    }

    public function test_it_filters_by_category(): void
    {
        $products = $this->get(route('shop', ['category' => $this->otherCategoryId]))->viewData('products');

        $this->assertSame(1, $products->total());
        $this->assertSame('Running Sneaker', $products->first()->product_name);
    }

    public function test_it_combines_search_with_a_category_filter(): void
    {
        // "Sneaker" exists, but not in the Apparel category.
        $products = $this->get(route('shop', [
            'q' => 'Sneaker',
            'category' => $this->categoryId,
        ]))->viewData('products');

        $this->assertSame(0, $products->total());
    }

    public function test_it_preserves_the_query_string_across_pages(): void
    {
        $response = $this->get(route('shop', ['category' => $this->categoryId]));

        // Without withQueryString() the filter would be dropped on page two.
        $response->assertOk()->assertSee('category='.$this->categoryId.'&amp;page=2', false);
    }

    public function test_the_homepage_does_not_load_the_entire_catalogue(): void
    {
        $response = $this->get(route('index'));

        $response->assertOk();
        $this->assertCount(8, $response->viewData('products'));
    }
}
