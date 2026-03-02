<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingCompanyCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_company_cities', function (Blueprint $table) {
            $table->foreignId('city_id')
                ->constrained('cities')
                ->references('id')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('shipping_company_id')
                ->constrained('shipping_companies')
                ->references('id')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_company_cities');
    }
}
