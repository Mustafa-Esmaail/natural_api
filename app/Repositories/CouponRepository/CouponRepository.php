<?php

declare(strict_types=1);

namespace App\Repositories\CouponRepository;

use App\Helpers\ResponseError;
use App\Http\Requests\Coupon\CheckCouponRequest;
use App\Models\Coupon;
use App\Models\Language;
use App\Models\OrderCoupon;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CouponRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Coupon::class;
    }

    public function couponsList(array $filter): Collection|array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'translation' => fn ($q) => $q
                    ->select('id', 'coupon_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn ($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'shop:id,logo_img',
                'shop.translation' => fn ($q) => $q
                    ->select('id', 'shop_id', 'locale', 'title')
                    ->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
            ])
            ->get();
    }

    public function couponsPaginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'translation' => fn ($q) => $q
                    ->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'coupon_id', 'locale', 'title'),
                'shop:id,logo_img',
                'shop.translation' => fn ($q) => $q
                    ->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'shop_id', 'locale', 'title'),
            ])
            ->whereHas('translation', function ($q) use ($locale) {
                $q->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }));
            })
            ->paginate(data_get($filter, 'perPage', 10));
    }

    public function show(Coupon $coupon): Coupon
    {
        $locale  = Language::where('default', 1)->first()?->locale;

        return $coupon->loadMissing([
            'translation' => fn ($q) => $q
                ->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'coupon_id', 'locale', 'title'),
            'shop:id,logo_img',
            'shop.translation' => fn ($q) => $q
                ->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'shop_id', 'locale', 'title'),
            'translations'
        ]);
    }

    public function checkCoupon(array $filter): array
    {
        $coupon = Coupon::where('name', data_get($filter, 'coupon'))
            ->where('shop_id', data_get($filter, 'shop_id'))
            ->where('qty', '>', 0)
            ->first();

        if (empty($coupon)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_249,
                'message' => __('errors.' . ResponseError::ERROR_249, locale: $this->language),
            ];
        }

        if ($coupon->expired_at < now()) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_250,
                'message' => __('errors.' . ResponseError::ERROR_250, locale: $this->language),
            ];
        }
        $validateCoupon = $this->model->validateCoupon($coupon, $filter);

        if (!$validateCoupon) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_250,
                'message' => __('errors.' . ResponseError::ERROR_250, locale: $this->language),
            ];
        } else {
            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $coupon,
            ];
        }
        // $result = OrderCoupon::where(function ($q) use ($filter) {
        //     $q
        //         ->where('user_id',data_get($filter,'user_id'))
        //         ->orWhere('user_id', auth('sanctum')->id());
        // })
        //     ->where('name', data_get($filter,'coupon'))
        //     ->count();


        // if (empty($result)) {
        //     return [
        //         'status' => true,
        //         'code'   => ResponseError::NO_ERROR,
        //         'data'   => $coupon,
        //     ];
        // }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_250,
            'message' => __('errors.' . ResponseError::ERROR_250, locale: $this->language),
        ];
    }
    public function couponPayments(array $filter): array
    {
        $coupon = Coupon::where('name', data_get($filter, 'coupon'))

            ->first();

        if (empty($coupon)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_249,
                'message' => __('errors.' . ResponseError::ERROR_249, locale: $this->language),
            ];
        } else {
            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $coupon,
            ];
        }



    }
}
