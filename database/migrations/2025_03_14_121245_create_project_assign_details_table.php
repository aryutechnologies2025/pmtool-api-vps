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
        Schema::create('project_assign_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreign('project_id')->references('id')->on('entry_processes')->onDelete('cascade');
            $table->integer('assign_user');
            $table->date('assign_date');
            $table->string('status');
            $table->date('status_date');
            $table->date('project_duration');
            $table->longText('comments')->nullable();
            $table->string('type');
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_assign_details');
    }
};
