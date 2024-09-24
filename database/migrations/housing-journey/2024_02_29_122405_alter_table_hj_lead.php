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
        Schema::table('hj_lead', function (Blueprint $table) {
            $table->integer('is_being_assisted')->default(false)->change();
            $table->integer('is_agreed')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_lead', function (Blueprint $table) {
            $table->dropColumn('is_being_assisted');
            $table->dropColumn('is_agreed');
        });
    }
};
