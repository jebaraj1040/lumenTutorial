<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class SmsLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'sms_log';
    protected $fillable = [
        'mobile_number', 'quote_id', 'cc_quote_id', 'master_product_id', 'source', 'source_page',
        'api_type', 'sms_template_type', 'request', 'response', 'is_email_sent'
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
