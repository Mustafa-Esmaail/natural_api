<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupons', function (Blueprint $table) {
            //
            $table->dateTime('start_date')->default(now())->change();
            $table->decimal('max_discount_value', 8, 2)->nullable();
            $table->decimal('min_amount_of_cart', 8, 2)->nullable();
            $table->integer('max_uses_per_user')->default(1);
            $table->json('allowed_users')->nullable();
            $table->json('specific_products')->nullable();
            $table->json('specific_brands')->nullable();
            $table->json('payment_methods')->nullable();
            $table->boolean('free_shipping')->default(false);
            $table->boolean('apply_free_delivery')->default(false);
            $table->json('categories')->nullable();
            $table->dropColumn(['type', 'free_shipping',
                'apply_free_delivery']);
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->enum('type', ['fix', 'percent', 'free_shipping'])->default('fix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            //

            $table->dropColumn([
                'start_date',
                'max_discount_value',
                'max_uses_per_user',
                'allowed_users',
                'specific_products',
                'specific_brands',
                'payment_methods',
                'free_shipping',
                'apply_free_delivery',
                'categories',
                'type',
                'min_amount_of_cart'
            ]);
            $table->enum('type', ['fix', 'percent'])->default('fix');


        });

    }
}
