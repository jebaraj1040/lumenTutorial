<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HjEligibility extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'hj_eligibility';
    protected $fillable = [
        'lead_id', 'quote_id', 'type', 'is_co_applicant', 'is_deviation',
        'loan_amount', 'tenure', 'created_at', 'updated_at'
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
}
