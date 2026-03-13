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
        Schema::table('activity_documents', function (Blueprint $table) {
            $table->index('files');
            $table->index('original_name');
            $table->index('created_by');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_documents', function (Blueprint $table) {
            $table->dropIndex(['files']);
            $table->dropIndex(['original_name']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['type']);
        });
    }
};
