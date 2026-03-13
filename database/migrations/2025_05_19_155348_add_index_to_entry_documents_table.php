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
        Schema::table('entry_documents', function (Blueprint $table) {
            $table->index('select_document');
            $table->index('file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_documents', function (Blueprint $table) {
            $table->dropIndex(['select_document']);
            $table->dropIndex(['file']);
        });
    }
};
