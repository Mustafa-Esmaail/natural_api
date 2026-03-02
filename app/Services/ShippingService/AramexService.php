<?php

declare(strict_types=1);

namespace App\Services\ShippingService;

use App\Models\Order;
use App\Models\ShippingCompany;
use Illuminate\Support\Facades\Log;
use Octw\Aramex\Aramex;
use InvalidArgumentException;
use RuntimeException;

class AramexService
{
    protected function getModelClass(): string
    {
        return ShippingCompany::class;
    }

    /**
     * Create a shipment with Aramex API.
     *
     * @param Order $order
     * @return void
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createShippingTransactionApi(Order $order): void
    {
        $data = $this->prepareShipmentData($order);

        $this->validateShipmentData($data);

        $options = ['timeout' => 60];
        Log::channel('aramex')->debug('Data sent to createShipment:', ['data' => $data]);

        $callResponse = Aramex::createShipment($data, $options);

        $this->handleApiResponse($callResponse);
    }

    /**
     * Prepare shipment data.
     *
     * @param Order $order
     * @return array
     */
    protected function prepareShipmentData(Order $order): array
    {
        return [
            'shipper' => [
                'name' => $order->shop?->translation?->title ?? '',
                'email' => $order->shop?->seller?->email ?? 'app@gmail.com',
                'phone' => $order->shop->phone ?? '+201149696690',
                'cell_phone' => $order->shop->phone ?? '201149696690',
                'country_code' => env('COUNTRY_CODE'),
                'city' => 'Cairo',
                'zip_code' => 32160,
                'line1' => $order->shop?->translation?->address ?? '',
                'line2' => $order->shop?->location?->country?->translation?->title . ' ,' . $order->shop?->location?->city?->translation?->title,
                'line3' => $order->shop?->location?->area?->translation?->title ?? '',
            ],
            'consignee' => [
                'name' => $order->user->full_name ?? "Client ID $order->user_id",
                'email' => $order->user->email,
                'phone' => $order->user->phone,
                'cell_phone' => $order->user->phone,
                'country_code' => env('COUNTRY_CODE'),
                'city' => 'Cairo',
                'zip_code' => $order->myAddress->zipcode ?? 32160,
                'line1' => $order->myAddress->location['address'] ?? 'cairo',
                'line2' => $order->myAddress->additional_details ?? 'aramex COUNTRY_CODE',
                'line3' => 'house number ' . ($order->myAddress->street_house_number ?? 'aramex COUNTRY_CODE'),
            ],
            'shipping_date_time' => time(),
            'due_date' => time(),
            'comments' => 'No Comment',
            'pickup_location' => 'at reception',
            'weight' => 2,
            'pickup_guid'=> null,
            'number_of_pieces' => 4,
            'description' => 'Products',
            'reference' => $order->id,
            'shipper_reference' => $order->id,
            'consignee_reference' => $order->id,
            'customs_value_amount' => $order->delivery_fee,
        ];
    }

    /**
     * Validate shipment data.
     *
     * @param array $data
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateShipmentData(array $data): void
    {
        $requiredFields = ['shipper', 'consignee', 'shipping_date_time', 'due_date', 'weight', 'number_of_pieces', 'description'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }
    }

    /**
     * Handle API response.
     *
     * @param object $callResponse
     * @return void
     * @throws RuntimeException
     */
    protected function handleApiResponse(object $callResponse): void
    {
        if (!empty($callResponse->error)) {
            foreach ($callResponse->errors as $errorObject) {
                Log::channel('aramex')->error("Aramex API Error", [
                    'code' => $errorObject->Code,
                    'message' => $errorObject->Message,
                    'callResponse' => $callResponse,
                ]);
                throw new RuntimeException("Aramex API Error: {$errorObject->Message}");
            }
        } else {
            $shipmentId = $callResponse->Shipments->ProcessedShipment->ID ?? null;
            $labelUrl = $callResponse->Shipments->ProcessedShipment->ShipmentLabel->LabelURL ?? null;

            // Process shipment data if necessary, e.g., save to database
            // e.g., $this->saveShipmentData($shipmentId, $labelUrl);
        }
    }
}
