<?php
declare(strict_types=1);

namespace App\Services\CouponService;

use App\Helpers\ResponseError;
use App\Models\Coupon;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;

class CouponService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Coupon::class;
    }

    public function create(array $data): array
    {
        try {
            $coupon = $this->model()->create($data);

            $tss=$this->setTranslations($coupon, $data);

            if ($coupon && data_get($data, 'images.0')) {
                $coupon->update(['img' => data_get($data, 'images.0')]);
                $coupon->uploads(data_get($data, 'images'));
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $coupon];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Coupon $coupon
     * @param array $data
     * @return array
     */
    public function update(Coupon $coupon, array $data): array
    {
        try {
            $coupon->update([
                ...$data,
                "allowed_users"     =>    isset($data["allowed_users"]) ? $data["allowed_users"] : [],
                "specific_products" =>    isset($data["specific_products"]) ? $data["specific_products"] : [],
                "specific_brands"   =>    isset($data["specific_brands"]) ? $data["specific_brands"] : [],
                "payment_methods"   =>    isset($data["payment_methods"])? $data["payment_methods"] : [],
                "categories"        =>    isset($data["categories"] )? $data["categories"] : [],
            ]);
            $this->setTranslations($coupon, $data);

            if (data_get($data, 'images.0')) {
                $coupon->galleries()->delete();
                $coupon->update(['img' => data_get($data, 'images.0')]);
                $coupon->uploads(data_get($data, 'images'));
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $coupon];
        }
        catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    public function delete(array $ids, ?int $shopId = null) {

        Coupon::whereIn('id', $ids)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->delete();

    }

}
