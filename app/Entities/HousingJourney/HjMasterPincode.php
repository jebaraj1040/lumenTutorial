<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterPincode extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_pincode';
    protected $fillable = [
        'code', 'area', 'city', 'district', 'state', 'is_serviceable', 'is_active', 'created_at', 'updated_at'
    ];
}
