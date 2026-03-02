<?php

declare(strict_types=1);

namespace App\Repositories\ShippingCompanyRepository;

use App\Models\ShippingCompany;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Language;
use Illuminate\Support\Collection;

class ShippingCompanyRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShippingCompany::class;
    }


    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var Career $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn ($query) => $query->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'cities.translation' => fn ($query) => $query->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }
    public function index(array $filter): Collection
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var Career $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn ($query) => $query->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'cities.translation' => fn ($query) => $query->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->orderBy(data_get($filter, 'column', 'id'), data_get($filter, 'sort', 'desc'))
            ->get();
    }

    /**
     * @param ShippingCompany $model
     * @return ShippingCompany|null
     */
    public function show(ShippingCompany $model): ShippingCompany|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->loadMissing([
            'translations',
            'translation' => fn ($query) => $query->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            })),
            'cities.translation' => fn ($query) => $query->when($this->language, fn ($q) => $q->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            })),
        ]);
    }

    /**
     * @param int $id
     * @return Model|null
     */
}
