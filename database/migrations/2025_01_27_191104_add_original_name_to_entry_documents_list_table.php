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
        Schema::table('entry_documents_list', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_documents_list', function (Blueprint $table) {
            $table->dropColumn('original_name');
        });
    }
};
