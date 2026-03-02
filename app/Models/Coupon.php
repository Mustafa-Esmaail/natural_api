<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\CouponFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Coupon
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $for
 * @property int $qty
 * @property int $max_uses_per_user
 * @property int $max_discount_value
 * @property int $min_amount_of_cart
 * @property float $price
 * @property Carbon|null $start_date
 * @property Carbon|null $expired_at
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|OrderCoupon[] $orderCoupons
 * @property-read int|null $order_coupons_count
 * @property-read Shop $shop
 * @property-read CouponTranslation|null $translation
 * @property-read Collection|CouponTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self checkCoupon(string $coupon, int $shopId)
 * @method static CouponFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self increment($column, $amount = 1, array $extra = [])
 * @method static Builder|self decrement($column, $amount = 1, array $extra = [])
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereExpiredAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereName($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereQty($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Coupon extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'allowed_users' => 'array',
        'specific_products' => 'array',
        'specific_brands' => 'array',
        'payment_methods' => 'array',
        'categories' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CouponTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(CouponTranslation::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function orderCoupons(): HasMany
    {
        return $this->hasMany(OrderCoupon::class, 'name', 'name');
    }

    public static function scopeCheckCoupon($query, $code, $shop_id)
    {
        return $query
            ->where('name', '=', $code)
            ->where('shop_id', $shop_id)
            ->where('qty', '>', 0)
            ->where('start_date', '<=', now()->format('Y-m-d H:i:s'))
            ->where('expired_at', '>=', now()->format('Y-m-d H:i:s'));
    }

    public function scopeFilter($query, array $filter)
    {
        $query
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', $type);
            })
            ->when(data_get($filter, 'for'), function ($q, $for) {
                $q->where('for', $for);
            })->when(data_get($filter, 'price'), function ($q, $price) {
                $q->where('price', $price);
            })
            ->when(data_get($filter, 'qty'), function ($q, $qty) {
                $q->where('qty', $qty);
            })
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) {
                $q->where('shop_id', $shopId);
            })
            ->when(data_get($filter, 'expired_from'), function ($q, $expiredFrom) use ($filter) {
                $expiredFrom = date('Y-m-d', strtotime($expiredFrom));

                $expiredTo = data_get($filter, 'expired_to', date('Y-m-d'));

                $expiredTo = date('Y-m-d', strtotime($expiredTo));

                $q->where([
                    ['expired_at', '>=', $expiredFrom],
                    ['expired_at', '<=', $expiredTo],
                ]);
            });
    }
    public  function validateCoupon($coupon, $array)
    {

        if (strcmp($coupon->name, $array['coupon']) !== 0) {

            return false;
        }

        if (isset($array['user_id'])) {
            if (!is_null($coupon->allowed_users)) {
                if (count($coupon->allowed_users) > 0  && !in_array(data_get($array, 'user_id'), $coupon->allowed_users)) {
                    return false;
                }
            }
        }

        if (isset($array['payment'])) {
            if (!is_null($coupon->payment_methods)) {

                if (count($coupon->payment_methods) > 0 && !in_array(data_get($array, 'payment'), $coupon->payment_methods)) {
                    return false;
                }
            }
        }
        if (isset($array['categories'])) {
            if (!is_null($coupon->categories)) {
                foreach (data_get($array, 'categories') as $category) {
                    if (count($coupon->categories) > 0 && !in_array($category, $coupon->categories)) {
                        return false;
                    }
                }
            }
        }
        if (isset($array['brands'])) {
            if (!is_null($coupon->specific_brands)) {
                foreach (data_get($array, 'brands') as $brand) {
                    if (count($coupon->specific_brands) > 0 && !in_array($brand, $coupon->specific_brands)) {
                        return false;
                    }
                }
            }
        }

        if (isset($array['products'])) {
            if (!is_null($coupon->specific_products)) {
                foreach (data_get($array, 'products') as $product) {
                    if (count($coupon->specific_products) > 0 && !in_array($product, $coupon->specific_products)) {
                        return false;
                    }
                }
            }
        }
        if (isset($array['price'])) {
            if ($coupon->min_amount_of_cart >= $array['price']) {
                return false;
            }
        }


        $couponeUses = CouponUse::where('coupon_id', $coupon->id)->where('user_id', data_get($array, 'user_id'))->count();


        if ($couponeUses >=  $coupon->max_uses_per_user) {

            return false;
        }
        return true;
    }
}
