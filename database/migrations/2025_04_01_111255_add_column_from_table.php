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
        Schema::table('rejected_form', function (Blueprint $table) {
            $table->string('journal_name')->nullable();
            $table->string('article_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rejected_form', function (Blueprint $table) {
            $table->dropColumn('journal_name');
            $table->dropColumn('article_id');
        });
    }
};
