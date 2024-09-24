<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterProject extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_project';
    protected $fillable = ['name', 'name_handle', 'code', 'builder', 'builder_handle', 'pincode_id', 'is_approved', 'is_active', 'created_at', 'updated_at'];

    public function pincodeDetail()
    {
        return $this->hasOne(HjMasterPincode::class, 'id', 'pincode_id');
    }
}
