<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ShippingOrderRepository\ShippingOrderRepository;
use App\Services\ShippingService\ShippingOrderService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\FilterParamsRequest;
use App\Helpers\ResponseError;
use App\Http\Requests\ShippingOrderRequest;
use App\Http\Resources\ShippingOrderResource;
use App\Models\ShippingOrder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShippingOrderController extends AdminBaseController
{
    //
    public function __construct(
        private ShippingOrderService $service,
        private ShippingOrderRepository $repository

    ) {
        parent::__construct();
    }

     /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return ShippingOrderResource::collection($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */


    /**
     * @param Career $career
     * @return JsonResponse
     */
    public function show(ShippingOrder $shippingOrder): JsonResponse
    {
        $result = $this->repository->show($shippingOrder);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShippingOrderResource::make($result)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Career $career
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(ShippingOrder $shippingOrder, ShippingOrderRequest $request): JsonResponse
    {

        $validated = $request->validated();

        $result = $this->service->update($shippingOrder, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ShippingOrderResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
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
}
