<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class OtpLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mongodb';
    protected $collection = 'otp_log';
    protected $fillable = [
        'mobile_number', 'loan_amount', 'master_product_id', 'is_otp_sent', 'otp_value', 'otp_flag', 'otp_expiry', 'is_otp_verified', 'is_otp_resent', 'api_source', 'api_source_page', 'api_type', 'api_header', 'api_url', 'api_request_type', 'api_data', 'api_status_code', 'max_attempt', 'api_status_message', 'created_at'
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
