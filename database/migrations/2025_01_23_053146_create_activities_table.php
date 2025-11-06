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
        Schema::create('activities', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('activity_type_id');
            $table->unsignedBigInteger('dealer_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('assigned_date');
            $table->date('due_date');
            $table->text('instructions')->nullable();
            $table->text('record_details')->nullable();
            $table->json('attachments')->nullable();
            $table->date('completed_date')->nullable();
            $table->enum('status', ['Pending', 'Completed'])->default('Pending');
            $table->timestamps();

            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
            $table->foreign('dealer_id')->references('id')->on('dealers')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
