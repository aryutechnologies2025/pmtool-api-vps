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
            $table->boolean('is_verify')->default(0)->after('reference_number_file');
            $table->string('writer_payment_status')->nullable()->after('is_verify');
            $table->string('reviewer_payment_status')->nullable()->after('writer_payment_status');
            $table->string('statistican_payment_status')->nullable()->after('reviewer_payment_status');
            $table->string('journal_payment_status')->nullable()->after('statistican_payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_processes', function (Blueprint $table) {
            $table->dropColumn('is_verify');
            $table->dropColumn('writer_payment_status');
            $table->dropColumn('reviewer_payment_status');
            $table->dropColumn('statistican_payment_status');
            $table->dropColumn('journal_payment_status');
        });
    }
};
