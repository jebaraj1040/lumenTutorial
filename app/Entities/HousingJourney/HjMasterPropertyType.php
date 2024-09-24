<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterPropertyType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_property_type';
    protected $fillable = ['name', 'handle', 'master_id', 'product_code', 'is_active', 'created_at', 'updated_at'];
}
