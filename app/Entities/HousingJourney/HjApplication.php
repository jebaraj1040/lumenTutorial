<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HjApplication extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'hj_application';
    protected $fillable = [
        'lead_id', 'name', 'quote_id', 'cc_quote_id', 'loan_amount',  'master_product_step_id', 'digital_transaction_no',
        'mobile_number',
        'bre1_loan_amount', 'bre1_updated_loan_amount',
        'bre2_loan_amount', 'offer_amount', 'master_origin_product_id',  'master_product_id',
        'previous_impression_id', 'current_impression_id', 'is_purchased', 'is_stp',
        'is_bre_execute', 'is_paid', 'is_traversed', 'payment_transaction_id',
        'cibil_score', 'cc_token', 'session_auth_token', 'auth_token',
        'bre_version_date', 'disposition_status',
        'disposition_sub_status', 'disposition_date', 'created_at', 'updated_at'
    ];

    public function impression()
    {
        return $this->hasMany(HjImpression::class, 'quote_id', 'quote_id');
    }

    public function AddressDetail()
    {
        return $this->hasOne(HjAddress::class, 'quote_id', 'quote_id');
    }

    public function lead()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
    public function pincodeData()
    {
        return $this->hasOne(HjMasterPincode::class, 'id', 'lead_id');
    }
    public function productData()
    {
        return $this->hasOne(HjMasterProduct::class, 'id', 'master_product_id');
    }

    public function masterproduct()
    {
        return $this->hasMany(HjMasterProduct::class, 'id', 'master_product_id');
    }

    public function masterProductOrigin()
    {
        return $this->hasMany(HjMasterProduct::class, 'id', 'master_origin_product_id');
    }

    public function masterproductstep()
    {
        return $this->hasMany(HjMasterProductStep::class, 'id', 'master_product_step_id');
    }
    public function eligibility()
    {
        return $this->hasOne(HjEligibility::class, 'quote_id', 'quote_id');
    }
    public function eligibilityData()
    {
        return $this->hasMany(HjEligibility::class, 'quote_id', 'quote_id');
    }
    public function personaldetail()
    {
        return $this->hasOne(HjPersonalDetail::class, 'quote_id', 'quote_id');
    }
    public function mappingProductStepCcStage()
    {
        return $this->belongsTo(HjMappingProductStepCcStage::class, 'master_product_step_id', 'master_cc_stage_id');
    }

    public function masterProductData()
    {
        return $this->hasOne(HjMasterProduct::class, 'id', 'master_product_id');
    }

    public function originMasterProductData()
    {
        return $this->hasOne(HjMasterProduct::class, 'id', 'master_origin_product_id');
    }

    public function paymentTransaction()
    {
        return $this->hasOne(HjPaymentTransaction::class, 'payment_transaction_id', 'payment_transaction_id');
    }

    public function paymentDetails()
    {
        return $this->hasMany(HjPaymentTransaction::class, 'quote_id', 'quote_id');
    }

    public function addressDetails()
    {
        return $this->hasMany(HjAddress::class, 'quote_id', 'quote_id');
    }

    public function employmentDetails()
    {
        return $this->hasMany(HjEmploymentDetail::class, 'quote_id', 'quote_id');
    }

    public function propertyDetails()
    {
        return $this->hasMany(HjPropertyLoanDetail::class, 'quote_id', 'quote_id');
    }

    public function documentDetails()
    {
        return $this->hasMany(HjDocument::class, 'quote_id', 'quote_id');
    }

    public function personalDetails()
    {
        return $this->hasMany(HjPersonalDetail::class, 'quote_id', 'quote_id');
    }
    public function coApplicant()
    {
        return $this->hasOne(HjMappingCoApplicant::class, 'quote_id', 'quote_id');
    }
}
