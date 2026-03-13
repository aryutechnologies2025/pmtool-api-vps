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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreign('project_id')->references('id')->on('entry_processes')->onDelete('cascade');
            $table->unsignedBigInteger('payment_id')->index();
            $table->foreign('payment_id')->references('id')->on('payment_processes')->onDelete('cascade');
            $table->string('payment_status')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('created_date')->nullable();
            // $table->string('createdby_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
