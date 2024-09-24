<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjEmploymentDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_employment_detail';
    protected $fillable = [
        'lead_id', 'quote_id',
        'employment_type_id', 'company_id', 'company_name', 'net_monthly_salary',
        'monthly_emi', 'total_experience', 'current_experience',
        'industry_segment_id', 'industry_type_id',
        'salary_mode_id', 'other_income',
        'net_monthly_sales', 'net_monthly_profit', 'gross_receipt', 'business_vintage', 'professional_type_id',
        'constitution_type_id', 'is_income_proof_document_available', 'created_at', 'updated_at'
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
    public function companyDetail()
    {
        return $this->hasOne(HjMasterCompany::class, 'id', 'company_id');
    }
    public function employmentType()
    {
        return $this->hasOne(HjMasterEmploymentType::class, 'id', 'employment_type_id');
    }
    public function employmentSalaryModeDetail()
    {
        return $this->hasOne(HjMasterEmploymentSalaryMode::class, 'id', 'salary_mode_id');
    }
    public function constitutionTypeDetail()
    {
        return $this->hasOne(HjMasterEmploymentConstitutionType::class, 'id', 'constitution_type_id');
    }
    public function industrySegment()
    {
        return $this->hasOne(HjMasterIndustrySegment::class, 'id', 'industry_segment_id');
    }

    public function industryType()
    {
        return $this->hasOne(HjMasterIndustryType::class, 'id', 'industry_type_id');
    }
    public function professionalType()
    {
        return $this->hasOne(HjMasterProfessionalType::class, 'id', 'professional_type_id');
    }

    public function applicationData()
    {
        return $this->hasOne(HjApplication::class, 'quote_id', 'quote_id');
    }
    public function eligibilityData()
    {
        return $this->hasMany(HjEligibility::class, 'quote_id', 'quote_id');
    }
}
