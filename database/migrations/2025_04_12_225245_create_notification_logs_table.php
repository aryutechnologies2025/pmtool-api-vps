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
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_process_id');
            $table->unsignedBigInteger('project_id'); // <-- added this
            $table->unsignedBigInteger('position_id');
            $table->string('message');
            $table->string('status')->default('unread'); // optional
            $table->timestamps();
        
            $table->foreign('entry_process_id')->references('id')->on('entry_processes')->onDelete('cascade');
            $table->foreign('project_id')->references('project_id')->on('entry_processes')->onDelete('cascade'); // <-- added this

    });
    }}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
