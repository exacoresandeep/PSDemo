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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('name');
            $table->string('designation');
            $table->string('email')->unique();
            $table->string('phone');
            $table->unsignedBigInteger('employee_type_id');
            $table->foreign('employee_type_id')->references('id')->on('employee_types');
            $table->string('password');
            $table->text('address');
            $table->string('photo')->nullable();
            $table->string('emergency_contact');
            $table->string('area')->nullable();
            $table->unsignedBigInteger('reporting_manager')->nullable(); 
            $table->foreign('reporting_manager')->references('id')->on('employees');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
