<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hj_address', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->unsigned()->index();
            $table->string('quote_id', 12)->index();
            $table->string('address1', 100);
            $table->string('address2', 100);
            $table->bigInteger('pincode_id')->unsigned()->index();

            $table->string('city', 50);
            $table->string('state', 50);
            $table->boolean('is_current_address')->default(true);
            $table->boolean('is_permanent_address')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('lead_id')->references('id')->on('hj_lead');
            $table->foreign('pincode_id')->references('id')->on('hj_master_pincode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_address');
    }
};
