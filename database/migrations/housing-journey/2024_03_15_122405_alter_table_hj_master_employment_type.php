<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            "UPDATE hj_master_employment_type
            set handle='self-employed-non-professional' where handle='self-employed-non-professinal'"
        );
        DB::statement(
            "UPDATE hj_master_employment_type set handle='self-employed-professional' 
            where handle='self-employed-professinal'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            "UPDATE hj_master_employment_type
            set handle='self-employed-non-professinal' where handle='self-employed-non-professional'"
        );
        DB::statement(
            "UPDATE hj_master_employment_type
            set handle='self-employed-professinal' where handle='self-employed-professional'"
        );
    }
};
