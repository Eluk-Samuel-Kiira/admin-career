<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('social_media_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('platform');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('followers_count')->default(0);
            $table->string('handle')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('country_code');
            $table->index('platform');
            $table->index('is_active');
            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_media_platforms');
    }
};