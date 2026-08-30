<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('seeker_profiles', function (Blueprint $table) {
            $table->json('cv_files')->nullable()->after('projects');
        });
    }

    public function down()
    {
        Schema::table('seeker_profiles', function (Blueprint $table) {
            $table->dropColumn('cv_files');
        });
    }
};