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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_type')->constrained('order_types')->onDelete('cascade');
            $table->string('order_category')->nullable();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('dealer_id')->constrained('dealers')->onDelete('cascade');
            $table->enum('payment_terms', ['Advance', 'Credit']);
            $table->decimal('advance_amount', 10, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('utr_number')->nullable();
            $table->string('attachment')->nullable();
            $table->date('billing_date');
            $table->date('reminder_date');
            $table->decimal('total_amount', 10, 2);
            $table->text('additional_information')->nullable();
            $table->enum('status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
            $table->string('vehicle_category')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->foreignId('created_by')->nullable(false)->constrained('employees')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
