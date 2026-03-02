<?php

declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;
use GuzzleHttp\Client;
use Throwable;
use Illuminate\Support\Facades\Log;

class NoonService extends BaseService
{
    /**
     * Return the associated model class for the service.
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * Process a payment transaction via Noon Payment gateway.
     *
     * @param array $data - The data needed for processing the payment.
     * @return PaymentProcess|Model - The result of the payment process.
     * @throws ApiErrorException|Throwable
     */
    public function processTransaction(array $data): Model|PaymentProcess
    {
        // Retrieve the payment with Noon tag and load its associated payload.
        /** @var Payment $payment */
        $payment = Payment::with('paymentPayload')
            ->where('tag', Payment::TAG_NOON)
            ->first();

        // Ensure the payment has a payload, and extract the payload data.
        $payload = $payment?->paymentPayload?->payload;

        // Get additional payload data using the provided data and existing payload.
        [$key, $before] = $this->getPayload($data, $payload);

        // Collect order IDs for reference
        $order_ids = collect($before['orders'])
            ->pluck('id')
            ->implode('-');

        // Fetch the front-end URL from environment variables
        $FRONT_URL = env('FRONT_URL');

        // Prepare the request body for Noon Payment API
        $body = [
            "apiOperation" => "INITIATE",
            "order" => [
                "reference" => $order_ids,
                "amount" => round($before['total_price'], 2),
                "currency" => 'SAR',
                "name" => $before['order']->user->full_name,
                "channel" => "web",
                "category" => "pay"
            ],
            "configuration" => [
                "tokenizeCc" => "true",
                "returnUrl" => $FRONT_URL.'webhook/payment/success',
                "locale" => "ar",
                "paymentAction" => "AUTHORIZE,SALE"
            ]
        ];

        // Determine the correct endpoint based on Noon mode (Test or Live)
        $endpoint = $payload['noon_test_mode'] === "Test"
            ? 'https://api-test.noonpayments.com/payment/v1/order'
            : 'https://api.noonpayments.com/payment/v1/order';

        // Log the request body being sent to Noon Payment API
        Log::channel('noon')->debug('Data sent to noon:', ['data' => $body]);

        // Send the HTTP request to Noon Payment API
        $client = new Client();
        $response = $client->post($endpoint, [
            'body' => json_encode($body),
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Key_' . $payload['noon_test_mode'] . " " . $payload['noon_Auth_key'],
            ],
        ]);

        // Decode the response body
        $responseBody = json_decode($response->getBody()->getContents(), true);

        // Log the response from Noon Payment API
        Log::channel('noon')->debug('responseBody from noon:', ['responseBody' => $responseBody]);

        // Update or create a payment process record based on the response
        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id'         => data_get($before, 'model_id'),
            'data'       => array_merge([
                'url'        => $responseBody['result']['checkoutData']['postUrl'] ?? null,
                'payment_id' => $payment->id,
            ], $before)
        ]);
    }

    /**
     * Handle the return URL and check the payment status.
     *
     * @return mixed - The result of the payment status check.
     */
    public function handleReturnUrl(Request $request)
    {
        $data = $request->all();
        // Extract the order ID and merchant reference from the returned data
        $orderId = $data['orderId'] ?? null;
        $merchantReference = $data['merchantReference'] ?? null;

        if (!$orderId || !$merchantReference) {
            // Log an error if necessary data is missing
            Log::error('Missing orderId or merchantReference from Noon payment return URL.', $data);
            throw new \Exception('Invalid return URL data');
        }

        // Retrieve the payment with Noon tag to get the API keys
        /** @var Payment $payment */
        $payment = Payment::with('paymentPayload')
            ->where('tag', Payment::TAG_NOON)
            ->first();

        // Ensure the payment has a payload, and extract the payload data.

        if (!$payment) {
            Log::error('Payment not found for the provided merchantReference.', ['merchantReference' => $merchantReference]);
            throw new \Exception('Payment not found.');
        }

        $payload = $payment?->paymentPayload?->payload;


        // Determine the correct endpoint based on Noon mode (Test or Live)
        $endpoint = "https://api-test.noonpayments.com/payment/v1/order/{$orderId}";


        // Prepare the HTTP client and headers
        $client = new Client();
        $headers = [
            'accept' => 'application/json',
            'Authorization' => 'Key_' . 'TEST' . " " . 'ZXlhY2xlYW5fa3NhLmRlZmF1bHQ6Zjc1MmIxODUwZGVlNDFhMGE1MjRlMDBiZTRkOTJkNTk=',
        ];

        try {
            // Send the request to Noon Payment API to check the payment status
            $response = $client->get($endpoint, ['headers' => $headers]);

            // Decode the response body
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Log the response from Noon Payment API
            Log::channel('noon')->debug('Payment status response from Noon:', ['response' => $responseBody]);

            // Handle payment status based on the response (e.g., success, failure)
            $paymentStatus = $responseBody['result']['order']['status'] ?? 'UNKNOWN';

            if ($paymentStatus === 'CAPTURED') {
                // Update payment record to mark it as successful

                $payment->update([
                    'status' => 'paid',
                ]);
            } else {
                // Handle other statuses (e.g., failed or pending)
                $payment->update([
                    'status' => 'rejected',
                ]);
            }


            return $responseBody;
        } catch (Throwable $e) {
            // Log the exception and rethrow it
            Log::error('Error checking payment status with Noon API.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

}
