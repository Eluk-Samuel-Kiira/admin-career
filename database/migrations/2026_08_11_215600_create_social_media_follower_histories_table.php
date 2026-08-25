<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('social_media_follower_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_media_platform_id')
                ->constrained('social_media_platforms')
                ->onDelete('cascade');
            $table->bigInteger('followers_count')->default(0);
            $table->timestamp('recorded_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('social_media_platform_id');
            $table->index('recorded_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_media_follower_histories');
    }
};