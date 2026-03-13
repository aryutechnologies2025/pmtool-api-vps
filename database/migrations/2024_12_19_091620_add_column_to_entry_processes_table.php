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
            $table->string('process_status')->nullable()->after('budget');
            $table->integer('journal')->nullable()->after('process_status');
            $table->date('journal_status_date')->nullable()->after('journal'); // Use timestamp for dates
            $table->date('journal_assigned_date')->nullable()->after('journal_status_date');
            $table->string('journal_status')->nullable()->after('journal_assigned_date');
            $table->string('journal_duration_unit')->nullable()->after('journal_status');
            $table->string('writer_duration_unit')->nullable()->after('writer_project_duration');
            $table->string('reviewer_duration_unit')->nullable()->after('reviewer_project_duration');
            $table->string('journal_project_duration')->nullable()->after('journal_status_date');
            $table->string('statistican_project_duration')->nullable()->after('journal_project_duration');
            $table->string('statistican_duration_unit')->nullable()->after('statistican_project_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_processes', function (Blueprint $table) {
            $table->dropColumn([
                'process_status',
                'journal',
                'journal_status_date',
                'journal_assigned_date',
                'journal_status',
                'journal_duration_unit',
                'writer_duration_unit',
                'reviewer_duration_unit',
                'journal_project_duration',
                'statistican_project_duration',
                'statistican_duration_unit'

            ]);
        });
    }
};