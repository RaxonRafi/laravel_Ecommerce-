<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->integer('regular_price');
            $table->integer('discounted_price')->nullable();
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->string('sku')->unique();
            $table->integer('category_id');
            $table->integer('subcategory_id');
            $table->longText('long_description');
            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('materials')->nullable();
            $table->text('other_info')->nullable();
            $table->string('product_thumbnail_photo')->default('default_product_thumbnail_photo.jpg');





            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
