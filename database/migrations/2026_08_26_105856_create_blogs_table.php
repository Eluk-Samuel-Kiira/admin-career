<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('cover_image_alt')->nullable();
            $table->string('cover_image_caption')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('reading_time')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_title')->nullable();
            $table->string('author_avatar')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('robots')->default('index,follow');
            $table->boolean('is_pinged')->default(false);
            $table->timestamp('last_pinged_at')->nullable();
            $table->boolean('submitted_to_indexing')->default(false);
            $table->timestamp('indexing_submitted_at')->nullable();
            $table->string('indexing_status')->nullable();
            $table->json('indexing_response')->nullable();
            $table->boolean('is_indexed')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('seo_score')->nullable();
            $table->integer('content_quality_score')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('featured_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('country_code');
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_published');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blogs');
    }
};