<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Cart\CartResource;
use App\Repositories\CartRepository\CartRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class CartReportController extends AdminBaseController
{
    //
    public function __construct(
        private CartRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function index(FilterParamsRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->cartReport($request->all());
            if (data_get($request, 'export') === 'excel') {
                return $this->successResponse(
                    __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                    $result
                );
            }


            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                CartResource::collection($result)
            );
        } catch (Exception $exception) {
            dd($exception);
            return $this->errorResponse(ResponseError::ERROR_400, $exception->getMessage());
        }
    }
}
