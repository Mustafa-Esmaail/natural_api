<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_companies', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 8, 2);
            $table->boolean('cash_on_delivery')->default(false);
            $table->decimal('minimum_amount', 8, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('shipping_companies_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_company_id')
                ->constrained('shipping_companies')
                ->references('id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();

            $table->unique(['shipping_company_id', 'locale'],'ship_comp_trans_company_id_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_companies');
    }
}
