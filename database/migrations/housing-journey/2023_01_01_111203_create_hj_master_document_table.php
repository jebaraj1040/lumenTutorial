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
        Schema::create('hj_master_document', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('handle', 100);
            $table->string('master_id');
            $table->integer('max_file');
            $table->integer('max_size_per_file_mb');
            $table->string('allowed_extensions', 20);
            $table->enum('max_duration_type', ['MONTH', 'YEAR']);
            $table->enum('max_duration', ['2', '6']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hj_master_document');
    }
};
