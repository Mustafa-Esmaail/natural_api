<?php

declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Model;
use Stripe\Exception\ApiErrorException;
use GuzzleHttp\Client;
use Throwable;
use Illuminate\Support\Facades\Log;


class TapService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws ApiErrorException|Throwable
     */
    public function processTransaction(array $data): Model|PaymentProcess
    {
        /** @var Payment $payment */
        $payment = Payment::with([
            'paymentPayload'
        ])
            ->where('tag', Payment::TAG_TAP)
            ->first();

        $payload = $payment?->paymentPayload?->payload;



        [$key, $before] = $this->getPayload($data, $payload);
        $order_ids = [];
        foreach ($before['orders'] as $order) {
            $order_ids[] = $order->id;
        }

        $order_ids = implode('-', $order_ids);


        $FRONT_URL = env('FRONT_URL');

        $body = [
            "amount" => $before['total_price'],
            "currency" => $before['currency'] ?? 'KWD',
            "customer_initiated" => true,
            "threeDSecure" => true,
            "save_card" => false,
            "description" => 'Payment for Order #' . $order_ids,
            "metadata" => [
                "udf1" => "Metadata 1"
            ],
            "reference" => [
                "transaction" => (string) $order_ids,
                "order" => (string) $order_ids
            ],
            "receipt" => [
                "email" => true,
                "sms" => true
            ],
            "customer" => [
                "first_name" => $before['user']->firstname ?? "Client ID " . $before['user_id'],
                "middle_name" => "",
                "last_name" => $before['user']->lastname,
                "email" => $before['user']->email,
                "phone" => [
                    "country_code" => 965,
                    "number" => $before['user']->phone
                ]
            ],
            // "merchant" => [
            //     "id" => "1234"
            // ],
            "source" => [
                "id" => "src_all"
            ],

            "post" => [
                "url" => $FRONT_URL . '/payment/success'
            ],
            "redirect" => [
                "url" => $FRONT_URL . '/payment/success'
            ]
        ];



        $endpoint = 'https://api.tap.company/v2/charges/';


        Log::channel('tap')->debug('Data sent to upayments:', ['data' => $body]);

        $client = new Client();

        $response = $client->request('POST', $endpoint, [
            'body' => json_encode($body),

            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $payload['tap_private_key'],
            ],
        ]);

        $responseBody = json_decode((string) $response->getBody(), true);

        foreach ($before['orders'] as $order) {
            $order->transaction->update([
                'payment_trx_id' =>$responseBody['id']
            ]);
        }


        Log::channel('tap')->debug('responseBody from upayments:', ['responseBody' => $responseBody]);


        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($before, 'model_id'),
            'data' => array_merge([
                'url'        => $responseBody['transaction']['url'],
                'payment_id' => $payment->id,
            ], $before)
        ]);
    }
}
