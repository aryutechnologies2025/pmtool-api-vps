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
        Schema::table('resubmission_form', function (Blueprint $table) {
            $table->string('date_of_rejected')->nullable();
            $table->string('date_of_submission')->nullable();
            $table->string('article_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resubmission_form', function (Blueprint $table) {
            $table->dropColumn('date_of_rejected');
            $table->dropColumn('date_of_submission');
            $table->dropColumn('article_id');
        });
    }
};
