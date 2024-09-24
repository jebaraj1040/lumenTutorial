<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Define common columns for the table.
     *
     * @param Blueprint $table
     * @return void
     */
    protected function defineCommonColumns(Blueprint $table)
    {
        $table->id();
        $table->string('name');
        $table->string('handle');
        $table->boolean('is_active')->default(true);
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('updated_at')->nullable();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hj_master_product_step', function (Blueprint $table) {
            $this->defineCommonColumns($table);
            $table->string('percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hj_master_product_step');
    }
};
