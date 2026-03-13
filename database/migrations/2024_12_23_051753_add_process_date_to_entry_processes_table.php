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
        Schema::table('entry_processes', function (Blueprint $table) {
            $table->string('process_date')->nullable()->after('process_status');
            $table->string('else_project_manager')->nullable()->after('process_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_processes', function (Blueprint $table) {
            //
        });
    }
};
