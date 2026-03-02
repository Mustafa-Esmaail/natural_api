<?php

namespace App\Models;

use App\Traits\Loadable;
use Awobaz\Compoships\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



/**
 * App\Models\PaymentReceipt
 *
 * @property int $id
 * @property string $img
 * @property string $payment_status
 * @property int $user_id
 * @property int $order_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Gallery[] $galleries
 * @property-read User|null $user
 * @property-read Order|null $order
 * @method static PaymentFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUrl($value)
 *
 * @mixin Eloquent
 */
class PaymentReceipt extends Model
{
    use HasFactory, Loadable;
    protected $fillable = [
        'img',
        'user_id',
        'payment_status',
        'order_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
