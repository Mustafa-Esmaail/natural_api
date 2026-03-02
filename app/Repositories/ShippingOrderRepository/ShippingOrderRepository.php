<?php

declare(strict_types=1);

namespace App\Repositories\ShippingOrderRepository;

use App\Models\ShippingOrder;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Language;
use Illuminate\Support\Collection;

class ShippingOrderRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShippingOrder::class;
    }
    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        /** @var ShippingOrder $model */
        $model = $this->model();
        return $model
            ->filter($filter)
            ->with([
                'order',
                'order.shop.location' => function ($query) {
                    $query->with('city.translation', 'area.translation');
                },
                'shippingCompany',
                'shippingCompany.translation',

                'order.myAddress' => function ($query) {
                    $query->with('city.translation', 'area.translation');
                },
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }


    public function show(ShippingOrder $model): ShippingOrder|null
    {
        return $model->loadMissing([
            'order',
            'shippingCompany',
            'order.deliveryPoint' => function ($query) {
                $query->with('city', 'area');
            },
            'order.myAddress',
        ]);
    }




}
