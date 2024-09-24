<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class PaymentLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mongodb';
    protected $collection = 'payment_log';
    protected $fillable = [
        'lead_id', 'quote_id', 'mobile_number', 'digital_transaction_no
        ', 'master_product_id', 'payment_transaction_id', 'api_source', 'api_source_page', 'api_type', 'api_header', 'api_url', 'api_request_type', 'api_data', 'api_status_code', 'api_status_message', 'created_at'
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
