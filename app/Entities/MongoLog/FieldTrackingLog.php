<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class FieldTrackingLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'field_tracking_log';
    protected $fillable = [
        'lead_id', 'quote_id', 'name', 'mobile_number',
        'master_product_id', 'pan', 'full_name', 'dob', 'gender',
        'email', 'is_property_identified', 'loan_amount',
        'employment_type_id', 'employment_type_value', 'company_id',
        'company_value', 'constitution_type_id', 'constitution_type_value',
        'salary_mode_id', 'salary_mode_value', 'net_monthly_salary',
        'monthly_emi', 'total_experience', 'current_experience',
        'other_income', 'industry_segment_id', 'industry_segment_value', 'industry_type_id',
        'industry_type_value',
        'net_monthly_sales', 'net_monthly_profit', 'gross_receipt',
        'business_vintage', 'professional_type_id', 'professional_type_value',
        'is_income_proof_document_available',
        'eligibility_loan_amount', 'eligibility_tenure',
        'co_applicant_pan', 'co_applicant_full_name',
        'co_applicant_dob', 'co_applicant_gender',
        'co_applicant_email', 'co_applicant_relationship_id', 'co_applicant_relationship_value',
        'co_applicant_employment_type_id', 'co_applicant_employment_type_value', 'co_applicant_company_id',
        'co_applicant_company_value', 'co_applicant_constitution_type_id', 'co_applicant_constitution_type_value',
        'co_applicant_salary_mode_id', 'co_applicant_salary_mode_value', 'co_applicant_net_monthly_salary',
        'co_applicant_monthly_emi', 'co_applicant_total_experience', 'co_applicant_current_experience',
        'co_applicant_other_income',
        'co_applicant_industry_segment_id', 'co_applicant_industry_segment_value', 'co_applicant_industry_type_id',
        'co_applicant_industry_type_value',
        'co_applicant_net_monthly_sales', 'co_applicant_net_monthly_profit', 'co_applicant_gross_receipt',
        'co_applicant_business_vintage', 'co_applicant_professional_type_id', 'co_applicant_professional_type_value',
        'co_applicant_is_income_proof_document_available',
        'address1', 'address2', 'pincode_id', 'pincode', 'city', 'state',
        'permanent_address1', 'permanent_address2',
        'permanent_pincode_id', 'permanent_pincode_value', 'permanent_city', 'permanent_state',
        'existing_loan_provider',
        'property_type_id', 'property_type_value', 'age', 'cost', 'property_purchase_from',
        'project_type', 'project_id', 'project_value', 'is_property_identified',
        'is_property_loan_free', 'property_purpose_id', 'property_purpose_value',
        'original_loan_amount', 'original_loan_tenure',
        'outstanding_loan_tenure', 'monthly_installment_amount',
        'plot_cost', 'construction_cost',
        'property_current_state_id', 'property_current_state_value', 'property_pincode_id',
        'property_pincode_value',
        'area', 'property_city', 'property_state', 'original_loan_amount',
        'document_position_id', 'master_document_id',
        'master_document_value', 'master_document_type_id', 'master_document_type_value',
        'document_type_extension', 'document_saved_location', 'document_file_name',
        'api_source_page', 'api_type', 'api_header', 'api_url', 'api_source', 'cc_quote_id', 'cc_auth_token',
        'api_request_type', 'api_data', 'api_status_code',
        'api_status_message', 'cc_token', 'cc_push_stage_id',
        'cc_push_sub_stage_id', 'cc_push_sub_stage_priority',
        'cc_push_block_for_calling', 'cc_push_status', 'cc_push_tag', 'created_timestamp', 'created_at'
    ];

    public $timestamps = false;
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
