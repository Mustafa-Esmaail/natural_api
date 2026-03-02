<?php

declare(strict_types=1);

namespace App\Services\ShippingService;

use App\Helpers\ResponseError;
use App\Models\ShippingCompany;
use App\Traits\SetTranslations;
use App\Services\CoreService;
use DB;
use Throwable;

class ShippingCompanyService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return ShippingCompany::class;
    }

    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var ShippingCompany $model */
                $model = $this->model()->create($data);
                $this->setTranslations($model, $data);
                $model->cities()->sync(data_get($data, 'cities'));

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
    public function update(ShippingCompany $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($model, $data) {

                $model->update($data);
                $this->setTranslations($model, $data);
                $model->cities()->sync(data_get($data, 'cities'));

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
