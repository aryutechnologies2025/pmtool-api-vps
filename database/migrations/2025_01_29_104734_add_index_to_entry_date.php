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
            $table->index('entry_date');
            $table->index('type_of_work');
            $table->index('institute');
            $table->index('writer');
            $table->index('writer_status');
            $table->index('reviewer');
            $table->index('reviewer_status');
            $table->index('statistican');
            $table->index('statistican_status');
            $table->index('journal');
            $table->index('journal_status');
            $table->index('hierarchy_level');
            $table->index('process_status');
            $table->index('created_by');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_processes', function (Blueprint $table) {
            $table->dropIndex(['entry_date']);
            $table->dropIndex(['type_of_work']);
            $table->dropIndex(['institute']);
            $table->dropIndex(['writer']);
            $table->dropIndex(['writer_status']);
            $table->dropIndex(['reviewer']);
            $table->dropIndex(['reviewer_status']);
            $table->dropIndex(['statistican']);
            $table->dropIndex(['statistican_status']);
            $table->dropIndex(['journal']);
            $table->dropIndex(['journal_status']);
            $table->dropIndex(['hierarchy_level']);
            $table->dropIndex(['process_status']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['email']);
        });
    }
};
