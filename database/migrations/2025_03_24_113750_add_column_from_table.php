<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_assign_details', function (Blueprint $table) {
            $table->string('type_of_article')->nullable();
            $table->string('review')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_assign_details', function (Blueprint $table) {
            $table->dropColumn('type_of_article');
            $table->dropColumn('review');
        });
    }
};