<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\ShippingCompany
 *
 * @property int $id
 * @property int $price
 * @property string $title
 * @property boolean $cash_on_delivery
 * @property int $minimum_amount
 * @property int $cash_fee
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|City[] $cities
 * @property Collection|ShippingCompanyTranslation[] $translations
 * @property ShippingCompanyTranslation|null $translation
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter($filter)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereModelId($value)
 * @method static Builder|self whereModelType($value)
 * @method static Builder|self whereData($value)
 * @mixin Eloquent
 */
class ShippingCompany extends Model
{
    use HasFactory;
    protected $fillable = ['title','price', 'cash_on_delivery', 'minimum_amount', 'cash_fee'];

    public function translations(): HasMany
    {
        return $this->hasMany(ShippingCompanyTranslation::class,);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ShippingCompanyTranslation::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'shipping_company_cities');
    }
    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(request()->is('api/v1/rest/*') && request('lang'), function ($q) {
                $q->whereHas('translation', fn ($query) => $query->where(function ($q) {
                    $locale = Language::where('default', 1)->first()?->locale;
                    $q->where('locale', request('lang'))->orWhere('locale', $locale);
                }));
            })
            ->when(isset($filter['cash_on_delivery']), fn ($q) => $q->where('cash_on_delivery', $filter['cash_on_delivery']))
            ->when(isset($filter['amount']), fn ($q) => $q->where('minimum_amount', '<=', $filter['amount']))
            ->when(data_get($filter, 'city_id'), fn ($q, $cityId) => $q->whereHas('cities', fn ($q) => $q->where('id', $cityId)))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where(function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search)->get();
                    });
                });
            });
    }
}
