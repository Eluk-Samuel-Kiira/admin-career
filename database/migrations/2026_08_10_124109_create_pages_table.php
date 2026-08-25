<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('template')->default('default');
            $table->string('featured_image')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('country_code');
            $table->index('is_active');
            $table->index('slug');
            $table->unique(['slug', 'country_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pages');
    }
};