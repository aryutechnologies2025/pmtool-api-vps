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
        Schema::table('projectlogs', function (Blueprint $table) {
            $table->unsignedBigInteger('assing_preview_id')->nullable();
            $table->foreign('assing_preview_id')->references('id')->on('projectlogs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projectlogs', function (Blueprint $table) {
            $table->dropForeign(['assing_preview_id']);
            $table->dropColumn('assing_preview_id');
        });
    }
};
