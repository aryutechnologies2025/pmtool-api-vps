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
        Schema::create('payment_processes', function (Blueprint $table) {
            $table->id();
            // Adding a project_id foreign key from entry_process table
            $table->unsignedBigInteger('project_id')->index();
            $table->foreign('project_id')->references('id')->on('entry_processes')->onDelete('cascade');
            $table->string('writer_payment')->nullable();
            $table->date('writer_payment_date')->nullable();
            $table->string('reviewer_payment')->nullable();
            $table->date('reviewer_payment_date')->nullable();
            $table->string('statistican_payment')->nullable();
            $table->date('statistican_payment_date')->nullable();
            $table->string('journal_payment')->nullable();
            $table->date('journal_payment_date')->nullable();
            $table->string('payment_status');
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_processes');
    }
};



// <?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     /**
//      * Run the migrations.
//      */
//     public function up(): void
//     {
//         Schema::create('payment_processes', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('processes_id')->constrained('processes')->onDelete('cascade');

//             // Adding a project_id foreign key from entry_process table
//             $table->foreignId('project_id')->constrained('entry_process')->onDelete('cascade'); // Linking project_id

//             $table->string('process_title');
//             $table->string('budget');
//             $table->string('author_payment_1');
//             $table->date('payment_date_1');
//             $table->string('author_payment_2');
//             $table->date('payment_date_2');
//             $table->string('author_payment_3');
//             $table->date('payment_date_3');
//             $table->string('journal_fee');
//             $table->date('Jounel_Payment_date');
//             $table->string('writer_payment');
//             $table->string('writer_payment_date');
//             $table->string('reviewer_payment');
//             $table->string('reviewer_payment_date');
//             $table->string('vendor_payment');
//             $table->string('vendor_payment_date');
//             $table->string('payment_received');
//             $table->date('payment_received_date');
//             $table->string('payment_status');
//             $table->boolean('status')->default(1);
//             $table->boolean('is_deleted')->default(0);
//             $table->timestamps();
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('payment_processes');
//     }
// };
