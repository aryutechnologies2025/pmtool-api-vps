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
            $table->string('reference_number')->after('created_by')->nullable();
            $table->string('reference_number_file')->after('reference_number')->nullable();
            $table->integer('writer_id')->after('project_id')->nullable();
            $table->integer('reviewer_id')->after('writer_payment_date')->nullable();
            $table->integer('statistican_id')->after('reviewer_payment_date')->nullable();
            $table->integer('journal_id')->after('statistican_payment_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_processes', function (Blueprint $table) {
            $table->dropColumn('reference_number');
            $table->dropColumn('reference_number_file');
            $table->dropColumn('writer_id');
            $table->dropColumn('reviewer_id');
            $table->dropColumn('statistican_id');
            $table->dropColumn('journal_id');
        });
    }
};
