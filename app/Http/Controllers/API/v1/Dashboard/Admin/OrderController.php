<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\OrderExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\DeliveryManUpdateRequest;
use App\Http\Requests\Order\OrderTrackingUpdateRequest;
use App\Http\Requests\Order\StatusUpdateRequest;
use App\Http\Requests\Order\StoreRequest;
use App\Http\Requests\Order\UpdateRequest;
use App\Http\Resources\OrderResource;
use App\Imports\OrderImport;
use App\Models\Order;
use App\Models\PushNotification;
use App\Models\ShippingOrder;
use App\Models\User;
use App\Repositories\OrderRepository\AdminOrderRepository;
use App\Repositories\OrderRepository\OrderRepository;
use App\Services\OrderService\OrderService;
use App\Services\OrderService\OrderStatusUpdateService;
use App\Services\ShippingService\ShippingService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderController extends AdminBaseController
{
    use Notification;

    public function __construct(
        private OrderRepository $repository,
        private AdminOrderRepository $adminRepository,
        private OrderService $service
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = $this->repository->ordersList();

        return OrderResource::collection($orders);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->all();

        return OrderResource::collection($this->adminRepository->ordersPaginate($filter));
    }

    /**
     * Display a listing of the resource.
     *
     * @param string $userId
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function userOrders(string $userId, FilterParamsRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user   = User::select(['id', 'uuid'])->where('uuid', $userId)->first();
        $filter = $request->merge(['user_id' => $user?->id])->all();

        return OrderResource::collection($this->adminRepository->userOrdersPaginate($filter));
    }

    /**
     * Display a listing of the resource.
     *
     * @param string $userId
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function userOrder(string $userId, FilterParamsRequest $request): JsonResponse
    {
        $orderDetails = $this->adminRepository->userOrder($userId, $request->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $orderDetails
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        foreach (data_get($result, 'data') as $order) {

            /** @var Order $order */
            $seller = $order?->shop?->seller;
            $firebaseToken = $seller?->firebase_token;

            $this->sendNotification(
                $order,
                is_array($firebaseToken) ? $firebaseToken : [],
                __('errors.' . ResponseError::NEW_ORDER, ['id' => $order->id], $seller?->lang ?? $this->language),
                __('errors.' . ResponseError::NEW_ORDER, ['id' => $order->id], $seller?->lang ?? $this->language),
                [
                    'id'     => $order->id,
                    'status' => $order->status,
                    'type'   => PushNotification::NEW_ORDER
                ],
                $seller?->id ? [$seller->id] : []
            );
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            OrderResource::collection(data_get($result, 'data')),
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->repository->orderById($id);

        if (!$order) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderResource::make($order)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());


        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            OrderResource::make(data_get($result, 'data')),
        );
    }
    public function UpdateOrderStatus(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->UpdateOrderStatus($request->input('ids'), $request->input('status'));

        if (count($result) > 0) {

            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_400,
                'message'   => __('errors.' . ResponseError::CANT_DELETE_ORDERS, [
                    'ids' => implode(', #', $result)
                ], locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Update Order DeliveryMan Update.
     *
     * @param int $orderId
     * @param DeliveryManUpdateRequest $request
     * @return JsonResponse
     */
    public function orderDeliverymanUpdate(int $orderId, DeliveryManUpdateRequest $request): JsonResponse
    {
        $result = $this->service->updateDeliveryMan($orderId, (int)$request->input('deliveryman_id'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            OrderResource::make(data_get($result, 'data')),
        );
    }

    /**
     * Update Order Status details by OrderDetail ID.
     *
     * @param int $id
     * @param StatusUpdateRequest $request
     * @return JsonResponse
     */
    public function orderStatusUpdate(int $id, StatusUpdateRequest $request): JsonResponse
    {

        /** @var Order $order */
        $order = Order::with([
            'deliveryman',
            'user.notifications',
            'orderDetails.stock',
            'shop',
            'shop.seller',
            'user.wallet',
            'transaction.paymentSystem',
            'notes',
            'shippingOrder.ShippingCompany'
        ])->find($id);

        if (!$order) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ]);
        }

        if (!$order->user) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_502,
                'message'   => __('errors.' . ResponseError::USER_NOT_FOUND, locale: $this->language)
            ]);
        }

        $result = (new OrderStatusUpdateService)->statusUpdate($order, $request->validated());

        if (data_get($request, 'status') === Order::STATUS_READY && $order->delivery_type == 'delivery') {


            (new ShippingService)->handleShippingMethods($order, $request->validated());
        }

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderResource::make(data_get($result, 'data')),
        );
    }

    /**
     * Update Order Status details by OrderDetail ID.
     *
     * @param int $orderId
     * @param OrderTrackingUpdateRequest $request
     * @return JsonResponse
     */
    public function orderTrackingUpdate(int $orderId, OrderTrackingUpdateRequest $request): JsonResponse
    {
        try {
            $result = $this->service->trackingUpdate($orderId, $request->validated());
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ]);
        }

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR),
            OrderResource::make($result),
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->destroy($request->input('ids'));

        if (count($result) > 0) {

            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_400,
                'message'   => __('errors.' . ResponseError::CANT_DELETE_ORDERS, [
                    'ids' => implode(', #', $result)
                ], locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/orders.xlsx';

        try {
            $filter = $request->merge(['language' => $this->language])->all();

            Excel::store(new OrderExport($filter), $fileName, 'public', \Maatwebsite\Excel\Excel::XLSX);

            return $this->successResponse('Successfully exported', [
                'path'      => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(statusCode: ResponseError::ERROR_508, message: $e->getMessage());
        }
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        try {

            Excel::import(new OrderImport($this->language), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(statusCode: ResponseError::ERROR_508, message: $e->getMessage());
        }
    }


    public function printAwb(FilterParamsRequest $request): JsonResponse
    {
        $filter = $request->merge(['language' => $this->language])->all();

        try {
            $labels = [];
            $orders = Order::whereHas('shippingOrder', fn ($q) => $q->whereNotNull('url'))
                ->filter($filter)->get();

            if (count($orders) === 0) {
                return $this->errorResponse(statusCode: ResponseError::ERROR_404, message: "Items not found");
            }

            foreach ($orders as $order) {
                $labels[] = $order->shippingOrder->url;
            }

            $output_directory = storage_path("app/public/awb/");
            $output_file_name = "output_awb_" . time() . ".pdf";
            $output_file_path = $output_directory . $output_file_name;

            // Ensure the output directory exists
            if (!file_exists($output_directory)) {
                mkdir($output_directory, 0755, true);
            }

            $cmd = "";
            // $cmd = "gswin64c  -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=\"" . $output_file_path . "\"";
            // $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=\"" . $output_file_path . "\"";
            $os = php_uname();

            if (stripos($os, 'Windows') !== false) {
                $cmd = "gswin64c  -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=\"" . $output_file_path . "\"";
            } else {
                $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=\"" . $output_file_path . "\"";
            }
            $is_true = false;

            foreach ($labels as $file) {
                // Download the file locally if it's a URL
                if (filter_var($file, FILTER_VALIDATE_URL)) {
                    $file_info = pathinfo($file);
                    $local_file_path = $output_directory . $file_info['basename'];

                    if (!file_exists($local_file_path)) {
                        file_put_contents($local_file_path, file_get_contents($file));
                    }

                    $file = $local_file_path;
                }

                if ($this->isPdfFile($file)) {
                    $cmd .= " \"" . $file . "\"";
                    $is_true = true;
                }
            }

            if ($is_true) {

                shell_exec($cmd);
                session()->put('labels', ['/storage/awb/' . $output_file_name]);
            }

            Log::info('AWB PDF files merged successfully', ['cmd' => $cmd, 'output' => session()->get('labels')]);

            return response()->json([
                'status' => 'success',
                'url' => url('storage/awb/' . $output_file_name),
            ]);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(statusCode: ResponseError::ERROR_508, message: $e->getMessage());
        }
    }


    function isPdfFile($filename)
    {
        $handle = fopen($filename, "rb");
        if (!$handle) {
            return false;
        }
        $header = fread($handle, 5);
        fclose($handle);
        return $header === "%PDF-";
    }
}
