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


class UpaymentsService extends BaseService
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
            ->where('tag', Payment::TAG_UPAYMENTS)
            ->first();

        $payload = $payment?->paymentPayload?->payload;






        // Stripe::setApiKey(data_get($payload, 'stripe_sk'));

        [$key, $before] = $this->getPayload($data, $payload);
        $order_ids = [];
        foreach ($before['orders'] as $order) {
            $order_ids[] = $order->id;
            $before['order'] = $order;
        }
        $order_ids = implode('-', $order_ids);


        $FRONT_URL = env('FRONT_URL');
        $body =
            [
                // 'products'=>
                // [
                //         'quantity' => 1,
                //         'price' => 100, // in cents
                //         'name' => 'Product 1',
                //         'description' => 'Product 1 Description',

                // ],
                'order' =>
                [
                    'id' => (string) $order_ids,
                    'amount' => $before['total_price'], // in cents
                    'currency' => $before['currency'] ?? 'KWD',
                    'description' => 'Payment for Order #' . $order_ids,
                ],
                'paymentGateway' => ['src' => "knet"],
                'language' => "en",
                'reference' => ['id' =>  (string) $order_ids],
                'customer' => [
                    'uniqueId' => (string)  $before['user_id'],
                    'name' => $before['user']->full_name ?? "Client ID ",
                    'email' => $before['user']->email,
                    'mobile' => $before['user']->uphone,
                ],
                'returnUrl' => $FRONT_URL . '/payment/success',
                'cancelUrl' => $FRONT_URL . '/payment/error',
                'notificationUrl' => 'http://127.0.0.1:8000',

            ];



        $endpoint = 'https://upayments.com/api/payment'; // Replace with UPayments API endpoint
        if ($payload['upayment_test_mode']) {
            $endpoint = 'https://sandboxapi.upayments.com/api/v1/charge';
        }

        Log::channel('upayments')->debug('Data sent to upayments:', ['data' => $body]);

        $client = new Client();

        $response = $client->request('POST', $endpoint, [
            'body' => json_encode($body),

            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer jtest123',
            ],
        ]);

        $responseBody = json_decode((string) $response->getBody(), true);
        Log::channel('upayments')->debug('responseBody from upayments:', ['responseBody' => $responseBody]);


        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($before, 'model_id'),
            'data' => array_merge([
                'url'        => $responseBody['data']['link'],
                'payment_id' => $payment->id,
            ], $before)
        ]);
    }
}
