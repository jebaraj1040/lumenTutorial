<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjPersonalDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_personal_detail';
    protected $fillable = [
        'lead_id', 'quote_id', 'full_name', 'dob', 'gender', 'email', 'pan', 'is_property_identified', 'loan_amount', 'unsubscribe',
        'created_at',
        'updated_at'
    ];

    public function leadDetail()
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
    public function appData()
    {
        return $this->hasOne(HjApplication::class, 'quote_id', 'quote_id');
    }
}
