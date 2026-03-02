<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;



/**
 * App\Models\Like
 *
 * @property int $id
 * @property int $order_id
 * @property int $company_id
 * @property string|null $awb
 * @property string|null $url
 * @property string $url
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read ShippingCompany $ShippingCompany
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLikableId($value)
 * @method static Builder|self whereLikableType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class ShippingOrder extends Model
{
    use HasFactory;

    protected $table ='shipping_orders';

    protected $fillable =
    [
        'order_id',
        'company_id',
        'awb',
        'url',
        'status'
    ];
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function ShippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'company_id');
    }
    public function scopeFilter($query, array $filter): void
    {
        $query->when(data_get($filter, 'order_id'), function ($q, $orderId) {
            $q->where('order_id', $orderId);
        })
            ->when(data_get($filter, 'company_id'), function ($q, $companyId) {
                $q->where('company_id', $companyId);
            })
            ->when(data_get($filter, 'status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(data_get($filter, 'awb'), function ($q, $awb) {
                $q->where('awb', $awb);
            })
            ->when(data_get($filter, 'url'), function ($q, $url) {
                $q->where('url', $url);
            });
    }
}
