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
        Schema::table('projectlogs', function (Blueprint $table) {
            // $table->index('project_id');
            $table->index('employee_id');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projectlogs', function (Blueprint $table) {
            // $table->dropIndex(['project_id']);
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
        });
    }
};
