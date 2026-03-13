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
        Schema::table('activities_project', function (Blueprint $table) {
            $table->index('activity');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities_project', function (Blueprint $table) {
            $table->dropIndex(['activity']);
            $table->dropIndex(['created_by']);
        });
    }
};
