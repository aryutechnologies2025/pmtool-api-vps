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
        Schema::table('assignee_status_list', function (Blueprint $table) {
            $table->index('activity');
            $table->index('created_by');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignee_status_list', function (Blueprint $table) {
            $table->dropIndex(['activity']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['is_read']);
        });
    }
};
