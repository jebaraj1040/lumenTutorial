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
        Schema::create('role_user_mapping', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('role_id')->unsigned()->index();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('created_by');
            $table->timestamp('updated_at')->nullable();
            $table->integer('updated_by')->nullable();
            $table->foreign('role_id')->references('id')->on('role_master');
            $table->foreign('user_id')->references('id')->on('user');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_user_mapping');
    }
};
