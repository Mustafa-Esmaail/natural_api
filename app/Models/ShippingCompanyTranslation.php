<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ShippingCompanyTranslation
 *
 * @property int $id
 * @property int $shipping_company_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereCareerId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class ShippingCompanyTranslation extends Model
{
    use HasFactory;
    protected $table ='shipping_companies_translations';
    public $timestamps = false;
    protected $fillable = ['locale', 'title', 'description', ];

    protected $guarded = ['id'];


}
