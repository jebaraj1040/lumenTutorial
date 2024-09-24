<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingProductType  extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    public $timestamps = false;
    protected $table = 'hj_mapping_product_type';
    protected $fillable = ['master_product_id', 'master_product_type_id', 'created_at', 'updated_at'];


    public function productType()
    {
        return $this->hasOne(HjMasterProductType::class, 'id', 'master_product_type_id');
    }
    public function productDetails()
    {
        return $this->hasOne(HjMasterProduct::class, 'id', 'master_product_id');
    }
}
