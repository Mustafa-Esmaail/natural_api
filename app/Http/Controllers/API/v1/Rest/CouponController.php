<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\CouponCheckRequest;
use App\Http\Requests\CouponPaymentRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Language;
use App\Repositories\CouponRepository\CouponRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends RestBaseController
{
    use ApiResponse;

    public function __construct(private CouponRepository $repository)
    {
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
        $coupons = $this->repository->couponsList($request->all());

        return CouponResource::collection($coupons);
    }

    public function show($id): JsonResponse

    {
        $coupon = Coupon::find($id);
        if ($coupon) {
            $locale  = Language::where('default', 1)->first()?->locale;
            $couponUses = CouponUse::where('coupon_id', $coupon->id)->count();
            $orderCoupons = $coupon->orderCoupons()->get();
            $totalOrderCouponPrice = $orderCoupons->sum(function ($orderCoupon) {
                return $orderCoupon->order->total_price  ?? 0;
            });
            $coupon->commissionPercentage = ($coupon->commission * $totalOrderCouponPrice) / 100;
            $coupon->total_order_coupon_price = $totalOrderCouponPrice;
            $coupon->coupon_uses = $couponUses;
            $coupon->load([
                'translation' => fn ($q) => $q
                    ->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'coupon_id', 'locale', 'title'),
                'translations',
                'orderCoupons.order',
            ]);


            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                CouponResource::make($this->repository->show($coupon, $totalOrderCouponPrice)),

            );
        } else {

            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }
    }



    /**
     * Handle the incoming request.
     *
     * @param CouponCheckRequest $request
     * @return JsonResponse
     */
    public function check(CouponCheckRequest $request): JsonResponse
    {
        $result = $this->repository->checkCoupon($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CouponResource::make(data_get($result, 'data'))
        );
    }
    public function couponPayments(CouponPaymentRequest $request): JsonResponse
    {
        $result = $this->repository->couponPayments($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CouponResource::make(data_get($result, 'data'))
        );
    }
}
