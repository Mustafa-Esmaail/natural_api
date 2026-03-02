<?php

declare(strict_types=1);

namespace App\Services\ShippingService;

use App\Models\Order;
use App\Services\CoreService;

class ShippingService extends CoreService
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * Create a shipment with  API.
     *
     * @param array $data
     * @return void
     */
    public function handleShippingMethods(Order $order): void
    {

        if ($order->ShippingOrder->ShippingCompany->title  === "Aramex") {
            (new AramexService())->createShippingTransactionApi($order);
        }
        if ($order->ShippingOrder->ShippingCompany->title  === "Smsa") {
            (new SmsaService())->createShippingTransactionApi($order,false);
        }
        if ($order->ShippingOrder->ShippingCompany->title  === "Smsa EyaSweet") {
            (new SmsaService())->createShippingTransactionApi($order,true);
        }
        if ($order->ShippingOrder->ShippingCompany->title=== "Redbox" ) {
            (new RedboxService())->createShippingTransactionApi($order);
        }
        if ($order->ShippingOrder->ShippingCompany->title=== "Hajer" ) {
            (new HajerService())->createShippingTransactionApi($order);
        }
    }

}
