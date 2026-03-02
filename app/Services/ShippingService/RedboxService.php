<?php

declare(strict_types=1);

namespace App\Services\ShippingService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\Settings;
use App\Models\ShippingCompany;
use App\Traits\SetTranslations;
use App\Services\CoreService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RedboxService extends CoreService
{
    protected function getModelClass(): string
    {
        return ShippingCompany::class;
    }

    /**
     * Create a shipment with Aramex API.
     *
     * @param array $data
     * @return void
     */
    public function createShippingTransactionApi(Order $order)
    {
        $parcels = [];

        $totalWeight = 0;
        $description = "";

        foreach ($order->orderDetails as $orderDetails) {
            $description .=  $orderDetails->quantity . ' x ' . $orderDetails->stock->product->translation->title . ', ' . $orderDetails->stock->product->translation->description . " \n";

            $parcels[] = [
                'name' => $orderDetails->stock->product->translation->title,
                'quantity' => $orderDetails->quantity,
                'description' => $description,
                'unitPrice' => $orderDetails->stock->price,
                'currency' => $order->currency->title ?? 'EG'
            ];

            $weight = $orderDetails->stock->weight * $orderDetails->quantity;
            $totalWeight += $weight;
        }

        $totalQuantity = array_sum(array_column($parcels, 'quantity'));
        $total = array_sum(array_column($parcels, 'total'));


        $data = [
            "business_id" => config('redbox.business_id'),
            "items" => $parcels,
            "reference" => $order->id,
            "customer_name" => $order->user->full_name ?? "Client ID $order->user_id",
            "customer_email" => $order->user->email ?? "mostafa.m.esmaail@gmail.com",
            "customer_phone" => $order->user->phone ?? "+201149696690",
            "customer_address" =>  $order->myAddress->location['address'] ?? env('COUNTRY_CODE'),
            "customer_city" => $order->myAddress->city->translation->title ?? env('COUNTRY_CODE'),
            "cod_currency" => $order->currency->title ?? 'KWD',
            "cod_amount" => $order->transaction->paymentSystem->tag === "cash" ? $order->transaction->price : 0,
            'weight_value' => $totalWeight,
            'weight_unit' => 'kg'
        ];
        Log::channel('redbox')->debug('redbox request: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        $callResponse = $this->createShipment($data);
        $callResponse = json_decode($callResponse);
        if ($callResponse->success) {
            $order->shippingOrder->update([
                'awb' => $callResponse->tracking_number,
                'url' => $callResponse->url_shipping_label,
            ]);
        } else {
            throw new \InvalidArgumentException("Shipment creation failed: $callResponse");
        }

        Log::channel('redbox')->info('redbox response: ' . json_encode($callResponse));
    }

    public function createShipment($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.redboxsa.com/api/business/v1/create-shipment");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . config('redbox.api_token')
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        return $responseData;
    }
}
