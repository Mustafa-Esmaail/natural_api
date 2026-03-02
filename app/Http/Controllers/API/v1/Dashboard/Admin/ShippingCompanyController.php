<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Http\Requests\ShippingCompanyRequest;
use App\Repositories\ShippingCompanyRepository\ShippingCompanyRepository;
use App\Services\ShippingService\ShippingCompanyService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Helpers\ResponseError;
use App\Http\Resources\ShippingCompanyResource;
use App\Models\ShippingCompany;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\FilterParamsRequest;



class ShippingCompanyController extends AdminBaseController
{
    public function __construct(
        private ShippingCompanyService $service,
        private ShippingCompanyRepository $repository
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

        return ShippingCompanyResource::collection($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(ShippingCompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ShippingCompanyResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param Career $career
     * @return JsonResponse
     */
    public function show(ShippingCompany $shippingCompany): JsonResponse
    {
        $result = $this->repository->show($shippingCompany);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShippingCompanyResource::make($result)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Career $career
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(ShippingCompany $shippingCompany, ShippingCompanyRequest $request): JsonResponse
    {

        $validated = $request->validated();

        $result = $this->service->update($shippingCompany, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ShippingCompanyResource::make(data_get($result, 'data'))
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
