<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
            ->constrained('orders')
            ->references('id')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('company_id')
            ->constrained('shipping_companies')
            ->references('id')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->string('awb')->nullable();
            $table->string('url')->nullable();
            $table->enum('status', ['new', 'on_way', 'shipped','canceled'])->default('new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_orders');
    }
}
