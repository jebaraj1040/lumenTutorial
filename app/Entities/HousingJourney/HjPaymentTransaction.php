<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjPaymentTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_payment_transaction';
    protected $fillable = [
        'lead_id',
        'quote_id',
        'payment_gateway_id',
        'payment_transaction_id',
        'digital_transaction_no',
        'gateway_transaction_id',
        'neft_payment_transaction_id',
        'bank_reference_no',
        'bank_name',
        'neft_bank_name',
        'neft_bank_ifsc_code',
        'neft_bank_beneficiary_name',
        'amount',
        'method',
        'mode',
        'gateway_status_code',
        'gateway_msg',
        'customer_var',
        'transaction_time',
        'transaction_type',
        'retrieval_reference_number',
        'status',
        'request',
        'response',
        'path',
        'sms_sent_status',
        'sms_sent_at',
        'email_sent_status',
        'email_sent_at',
        'reason',
        'conversion_rate',
        'billed_amount',
        'sur_charge',
        'merchant_name',
        'created_at',
        'updated_at'
    ];
    public function lead()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
    public function payment()
    {
        return $this->hasOne(HjpaymentGateway::class, 'id', 'payment_gateway_id');
    }
}
