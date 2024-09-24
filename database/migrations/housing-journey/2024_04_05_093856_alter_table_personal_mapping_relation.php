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
        Schema::table('hj_mapping_applicant_relationship', function (Blueprint $table) {
            $table->bigInteger('application_id')->after('relationship_id')->unsigned()->index();
            $table->string('quote_id', 12)->after('lead_id')->index();
            $table->foreign('application_id')->references('id')->on('hj_application');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hj_mapping_applicant_relationship', function (Blueprint $table) {
            $table->dropColumn('application_id');
            $table->dropColumn('quote_id');
        });
    }
};
