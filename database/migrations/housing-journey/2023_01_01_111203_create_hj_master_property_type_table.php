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
        Schema::create('hj_master_property_type', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('handle', 100);
            $table->string('product_code', 10);
            $table->string('master_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // Adds 'created_at' and 'updated_at' columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_master_property_type');
    }
};
