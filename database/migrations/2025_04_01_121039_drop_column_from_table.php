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
        Schema::table('payment_processes', function (Blueprint $table) {
            $table->dropColumn('writer_id');
            $table->dropColumn('writer_payment');
            $table->dropColumn('writer_payment_date');
            $table->dropColumn('reviewer_id');
            $table->dropColumn('reviewer_payment');
            $table->dropColumn('reviewer_payment_date');
            $table->dropColumn('statistican_id');
            $table->dropColumn('statistican_payment');
            $table->dropColumn('statistican_payment_date');
            $table->dropColumn('journal_id');
            $table->dropColumn('journal_payment');
            $table->dropColumn('journal_payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_processes', function (Blueprint $table) {
            
        });
    }
};
