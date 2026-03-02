<?php

declare(strict_types=1);

namespace App\Services\ShippingService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\ShippingCompany;
use App\Services\CoreService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log; // Added for logging


class SmsaService extends CoreService
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
    public function createShippingTransactionApi(Order $order, $new_samsa = false)
    {

        $parcels = [];

        $totalWeight = 0;
        $description = "";

        foreach ($order->orderDetails as $orderDetails) {
            $parcels[] = [
                'name' => $orderDetails->stock->product->translation->title,
                'quantity' => $orderDetails->quantity,
                'description' => $orderDetails->stock->product->translation->description,
                'unitPrice' => $orderDetails->stock->price,
                'currency' => $order->currency->title ?? 'EG'
            ];
            $weight = $orderDetails->stock->weight * $orderDetails->quantity;
            $totalWeight += $weight;
            $description .=  $orderDetails->quantity . ' x ' . $orderDetails->stock->product->translation->title . " \n";
        }
        $totalQuantity = array_sum(array_column($parcels, 'quantity'));
        // $total = array_sum(array_column($parcels, 'total'));
        $data = [
            "passkey" => $new_samsa ? config('smsa.smsa_pass_key_new') : config('smsa.pass_key'),
            "refno" => $order->id,
            "sentDate" => date('Y-m-d'),
            "idNo" => $order->user_id,
            "cName" => $order->user->full_name ?? "Client ID $order->user_id",
            "cntry" => $order->myAddress->country->translation->title ?? 'kuwait',
            "cCity" => $order->myAddress->city->translation->title ?? 'kuwait',
            "cZip" => "",
            "cPOBox" => "",
            "cMobile" => $order->user->phone ?? "",
            "cTel1" => "",
            "cTel2" => "",
            "cAddr1" => $order->myAddress->address ?? 'kuwait',
            "cAddr2" => $order->myAddress->street_house_number ?? 'aramex COUNTRY_CODE',
            "shipType" => $new_samsa ? config('smsa.shipType_new') : config('smsa.shipType'),
            "PCs" => (int) $totalQuantity,
            "cEmail" => $order->user->email ?? "",
            "carrValue" => "",
            "carrCurr" => "",
            "codAmt" => $order->transaction->paymentSystem->tag === "cash" ? $order->transaction->price : 0,
            "weight" => $totalWeight,
            "itemDesc" => $description,
            "custVal" => "",
            "custCurr" => "",
            "insrAmt" => "",
            "insrCurr" => "",
            "sName" => $order->shop?->translation?->title ?? '',
            "sContact" => $order->shop?->seller?->email ?? $order->shop?->translation?->title,
            "sAddr1" => $order->shop?->translation?->address ?? '',
            "sAddr2" => $order->shop?->location?->country?->translation?->title . ' ,' . $order->shop?->location?->city?->translation?->title,
            "sCity" => $order->shop?->location?->city?->translation?->title ?? "",
            "sPhone" => $order->shop->phone ?? "",
            "sCntry" => $order->shop?->location?->country?->translation?->title ?? '',
            "prefDelvDate" => "",
            "gpsPoints" => ""
        ];


        Log::channel('smsa')->debug('Data sent to createShipment:', ['data' => $data]);
        $AWBNumber = "";
        $callResponse = "";
        if ($new_samsa == true) {
            $callResponse = $this->createShipmentSoap($data);
        } else {
            $callResponse = $this->createShipment($data);

            if (str_contains($callResponse, 'Failed')) {

                // Handle the failure case
                throw new \InvalidArgumentException("Shipment failed: $callResponse");
                Log::channel('smsa')->error('Shipment creation failed', ['response' => $callResponse]);
            } else {
                // Handle the success case
                Log::channel('smsa')->info('Shipment created successfully', ['response' => $callResponse]);
                $AWBNumber = str_replace('"', '', $callResponse);

                $awbPdf = $this->getPdf($AWBNumber);
                $order->shippingOrder->update([
                    'awb' => $AWBNumber,
                    'url' => $awbPdf,
                ]);
            }
        }
    }
    public function createShipment($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://track.smsaexpress.com/SecomRestWebApi/api/addship");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        return $responseData;
    }
    function createShipmentSoap($data)
    {
        $wsdl = "https://track.smsaexpress.com/SECOM/SMSAwebService.asmx?WSDL";
        $soapAction = "http://track.smsaexpress.com/secom/addShipPDF";

        $options = [
            'trace' => 1,
            'exceptions' => true,
            // 'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $param=$data;



        Log::channel('smsa')->debug('Data sent to createShipmentNew:', ['data' => $data]);

        // try {
            $client = new \SoapClient($wsdl, $options);


            // Assuming you want to use addShipPDF
            $response = $client->__soapCall("addShip", [$param]);
            // $response = $client->addShip([$data]);
            dd($param,$response);

            $responseData = json_decode(json_encode($response), true);

            Log::channel('smsa')->debug('ResponseData from createShipmentNew:', ['responseData' => $responseData]);

            return response()->json($response);
        // } catch (\SoapFault $fault) {
        //     Log::channel('smsa')->error('SOAP Fault: ' . $fault->getMessage(), [
        //         'faultcode' => $fault->faultcode,
        //         'faultstring' => $fault->faultstring,
        //         'detail' => $fault->detail
        //     ]);

        //     // Handle the error appropriately, e.g., return an error response or log the error
        //     return response()->json(['error' => 'SOAP Fault: ' . $fault->getMessage()], 500);
        // }
    }

    public function getPdf($awb)
    {
        $data = [
            "passkey" => config('smsa.pass_key'),
            'awbNo' => $awb
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://track.smsaexpress.com/SecomRestWebApi/api/getPDF?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);

        $awbPath = 'smsa_labels/' . $awb . '.pdf';
        file_put_contents(public_path($awbPath), base64_decode($responseData));

        return url($awbPath);
    }
}
