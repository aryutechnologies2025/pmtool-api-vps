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
            $table->string('assign_date')->nullable()->change();
            $table->string('status_date')->nullable()->change();
            $table->string('project_duration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_assign_details', function (Blueprint $table) {
            $table->string('assign_date')->nullable(false)->change();
            $table->string('status_date')->nullable()->change();
            $table->string('project_duration')->nullable()->change();
        });
    }
};
