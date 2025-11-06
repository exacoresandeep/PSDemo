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
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_code')->unique(); 
            $table->string('dealer_name');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->string('user_zone');
            $table->string('pincode');
            $table->string('state');
            $table->string('district');
            $table->string('taluk');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
