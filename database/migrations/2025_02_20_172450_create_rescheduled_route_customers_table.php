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
        Schema::create('rescheduled_route_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rescheduled_route_id')->constrained('rescheduled_routes')->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_type'); // Dealer or Lead
            $table->string('location');
            $table->string('status')->default('pending'); // Initial status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rescheduled_route_customers');
    }
};
