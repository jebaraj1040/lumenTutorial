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
        Schema::table('hj_application', function (Blueprint $table) {
            $table->datetime('bre_version_date')->after('is_bre_execute');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_application', function (Blueprint $table) {
            $table->dropColumn('bre_version_date');
            $table->dropSoftDeletes();
        });
    }
};
