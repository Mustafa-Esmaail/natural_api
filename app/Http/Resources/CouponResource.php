<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Brand;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\CouponUse;
use App\Models\OrderCoupon;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Coupon|JsonResource $this */
        $allowed_users = !empty($this->allowed_users) && $this->allowed_users != null ? $this->allowed_users : [];
        $categories = !empty($this->categories) && $this->categories != null ? $this->categories : [];
        $specific_brands = !empty($this->specific_brands) && $this->specific_brands != null ? $this->specific_brands : [];
        $specific_products = !empty($this->specific_products) && $this->specific_products != null ? $this->specific_products : [];
        $payment_methods = !empty($this->payment_methods) && $this->payment_methods != null ? $this->payment_methods : [];



        return [
            'id'                    => (int) $this->id,
            'name'                  => (string) $this->name,
            'type'                  => $this->when($this->type, (string) $this->type),
            'for'                   => $this->when($this->for,  $this->for),
            'qty'                   => $this->when($this->qty, (int) $this->qty),
            'price'                 => $this->when($this->price, (float) $this->price),
            'commission'            => $this->when($this->commission, (float) $this->commission),
            'expired_at'            => $this->when($this->expired_at, $this->expired_at),
            'start_date'            => $this->when($this->start_date, $this->start_date),
            'max_uses_per_user'     => $this->when($this->max_uses_per_user, (int) $this->max_uses_per_user),
            'max_discount_value'    => $this->when($this->max_discount_value, (int) $this->max_discount_value),
            'min_amount_of_cart'    => $this->when($this->min_amount_of_cart, (int) $this->min_amount_of_cart),
            'shop_id'               => $this->when($this->shop_id, $this->shop_id),
            'img'                   => $this->img,
            'created_at'            => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'allowed_users'         => UserResource::collection(User::whereIn('id', $allowed_users)->get()),
            'categories'            => CategoryResource::collection(Category::whereIn('id', $categories)->with('translation')->get()),
            'brands'                => BrandResource::collection(Brand::whereIn('id', $specific_brands)->get()),
            'products'              => ProductResource::collection(Product::whereIn('id', $specific_products)->with('translation')->get()),
            'payments'              => PaymentResource::collection(Payment::whereIn('id', $payment_methods)->get()),
            'total_order_coupon_price' => $this->total_order_coupon_price ?? 0,
            'commissionPercentage'  =>  $this->commissionPercentage ?? 0,
            'coupon_uses'           => $this->coupon_uses ?? 0,

            // Relation
            'translation'           => TranslationResource::make($this->whenLoaded('translation')),
            'translations'          => TranslationResource::collection($this->whenLoaded('translations')),
            'galleries'             => GalleryResource::collection($this->whenLoaded('galleries')),
            'shop'                  => ShopResource::make($this->whenLoaded('shop')),
            'orderCoupons'          => OrderCouponResource::collection($this->whenLoaded('orderCoupons')),


        ];
    }
}
