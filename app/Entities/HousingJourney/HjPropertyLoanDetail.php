<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HjPropertyLoanDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'hj_property_loan_detail';
    protected $fillable = [
        'lead_id', 'quote_id',
        'existing_loan_provider', 'existing_loan_provider_name', 'property_type_id', 'age',
        'requirement_type', 'project_id', 'cost', 'property_purchase_from',
        'project_type', 'project_name', 'is_property_identified',
        'is_property_loan_free', 'is_existing_property', 'property_purpose_id', 'pincode_id',
        'area', 'city', 'state', 'original_loan_amount',
        'original_loan_tenure', 'outstanding_loan_amount',
        'outstanding_loan_tenure', 'property_current_state_id',
        'plot_cost', 'construction_cost',
        'monthly_installment_amount',
        'created_at', 'updated_at'
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

    public function propertyCurrentState()
    {
        return $this->hasOne(HjMasterPropertyCurrentState::class, 'id', 'property_current_state_id');
    }

    public function propertyType()
    {
        return $this->hasOne(HjMasterPropertyType::class, 'id', 'property_type_id');
    }

    public function propertyPurpose()
    {
        return $this->hasOne(HjMasterPropertyPurpose::class, 'id', 'property_purpose_id');
    }
    public function pincodeDetail()
    {
        return $this->hasOne(HjMasterPincode::class, 'id', 'pincode_id');
    }
    public function project()
    {
        return $this->hasOne(HjMasterProject::class, 'id', 'project_id');
    }

    public function loanProvider()
    {
        return $this->hasOne(HjMasterIfsc::class, 'id', 'existing_loan_provider');
    }
}
