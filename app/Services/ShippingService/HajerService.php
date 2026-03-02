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
use Illuminate\Support\Facades\Log; // Added for logging

class HajerService extends CoreService
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

    public function authenticate()
    {

        $data = [

            'email' => config('hajer.email'),
            'password' => config('hajer.password'),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://alhajer.quadra-soft.net/api/Auth/Login");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        $responseData = json_decode($responseData);
        Log::channel('hajer')->info('token_hajer: ' . json_encode($responseData->data->Token->access_token));
        return $responseData->data->Token->access_token ?? null;
    }
    public function createShippingTransactionApi(Order $order)
    {

        $token = $this->authenticate();

        if ($token) {
            $parcels = [];
            $products = [];
            $description = "";
            foreach ($order->orderDetails as $orderDetails) {
                $parcels[] = [
                    'name' => $orderDetails->stock->product->translation->title,
                    'quantity' => $orderDetails->quantity,
                    'description' => $orderDetails->stock->product->translation->description,
                    'unitPrice' => $orderDetails->stock->price,
                    'currency' => $order->currency->title ?? 'EG',
                    'weight' => $orderDetails->stock->weight ?? 0
                ];

                $weight = $orderDetails->stock->weight ?? 0;
                $quantity = $orderDetails->quantity;
                $price = $orderDetails->stock->price * $quantity;

                $description .=  $quantity . ' x ' . $orderDetails->stock->product->translation->title . ', ' . $orderDetails->stock->product->translation->description . " \n";
                $parcels[] = [
                    'weight' => $weight,
                    'quantity' => $quantity,
                    'total' => $price
                ];
                $products[] = [
                    'sku' => (string)"8683649871112",
                    'quantity' => $orderDetails->quantity
                ];
            }
            $totalQuantity = array_sum(array_column($parcels, 'quantity'));
            $total = array_sum(array_column($parcels, 'total'));

            $totalWeight = array_sum(array_column($parcels, 'weight'));


            $data = [
                "items" => [$totalQuantity],
                "Products" => $products,
                "receiver" => [
                    "name" =>  $order->user->full_name ?? "Client ID $order->user_id",
                    "city_id" => $order->myAddress->city->id,
                    "address" => $order->myAddress->country->translation->title . ', ' .
                        $order->myAddress->city->translation->title .  ', ' .
                        $order->myAddress->address . ', ' .
                        ($order->myAddress->street_house_number ?? ''),
                    "phone" => $order->user->phone ?? "",
                    "email" =>  $order->user->email ?? ""
                ],
                "number_of_boxes" =>  1,
                "price" =>  $order->transaction->paymentSystem->tag === "cash" ? $order->transaction->price : '0',
                "reference" =>  (string) $order->id,
                "payment_method" => $order->transaction->paymentSystem->tag === "cash" ? 'COD' : 'CC',
                "order_status_id" => 1,
                "description" => $description,


            ];


            Log::channel('hajer')->info('Hajer request: ' . json_encode($data));

            $callResponse = $this->createShipment($data);

            // Log::useFiles(storage_path() . '/logs/Hajer-' . date('Y-m-d') . '.log');channel('hajer')->
            Log::channel('hajer')->info('Hajer response: ' . json_encode($callResponse));

            $order->shippingOrder->update([
                'awb' => $callResponse->data->barCode,
            ]);
        }
    }

    public function createShipment($data)
    {
        $token = $this->authenticate();

        if ($token) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://alhajer.quadra-soft.net/api/Order");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            $responseData = json_decode($responseData);
            return $responseData;
        }
    }
}
