<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjAddress extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_address';
    protected $fillable = [
        'lead_id', 'quote_id', 'address1', 'address2', 'pincode_id', 'area', 'city', 'state', 'is_current_address', 'is_permanent_address', 'created_at',
        'updated_at'
    ];
    public function lead()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
    public function productStepMaster()
    {
        return $this->hasManyThrough(HjMasterProductStep::class, HjImpression::class, 'quote_id', 'id', 'quote_id', 'master_product_step_id');
    }
    public function productMaster()
    {
        return $this->hasOneThrough(HjMasterProduct::class, HjImpression::class, 'quote_id', 'id', 'quote_id', 'master_product_id');
    }
    public function pincodeDetail()
    {
        return $this->hasOne(HjMasterPincode::class, 'id', 'pincode_id');
    }
}
