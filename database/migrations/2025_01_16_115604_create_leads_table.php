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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_type')->constrained('customer_types')->onDelete('cascade')->nullable();
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('address');
            $table->text('instructions');
            $table->text('record_details')->nullable();
            $table->json('attachments')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('status', ['Opened', 'Follow Up', 'Converted', 'Deal Dropped'])->default('Opened');
            $table->foreignId('created_by')->constrained('employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
