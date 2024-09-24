<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingProdTypePropPurpose  extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_mapping_prod_type_prop_purpose';
    protected $fillable = ['master_product_type_id', 'master_property_purpose_type_id'];
}
