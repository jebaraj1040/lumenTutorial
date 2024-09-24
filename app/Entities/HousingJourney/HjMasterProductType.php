<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterProductType  extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_product_type';
    protected $fillable = ['name', 'handle', 'is_active', 'created_at', 'updated_at'];
}
