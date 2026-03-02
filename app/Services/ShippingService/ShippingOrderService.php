<?php

declare(strict_types=1);

namespace App\Services\ShippingService;

use App\Helpers\ResponseError;
use App\Models\ShippingOrder;
use App\Services\CoreService;
use DB;
use Throwable;

class ShippingOrderService extends CoreService
{

    protected function getModelClass(): string
    {
        return ShippingOrder::class;
    }

    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {
                /** @var ShippingOrder $model */
                return  $model = $this->model()->create($data);

            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {

            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }

    /**
     * Update specified Shop model.
     * @param ShippingCompany $model
     * @param array $data
     * @return array
     */
    public function update(ShippingOrder $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($model, $data) {

                $model->update($data);

                return $model;
            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    /**
     * Delete model.
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {
        return $this->remove($ids);
    }
}
