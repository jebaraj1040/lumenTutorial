<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class LeadAcquisitionLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'lead_acquisition_log';
    protected $fillable = [
        'mobile_number', 'master_product_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer', 'query_string', 'api_source', 'api_source_page', 'api_type', 'api_header', 'api_url', 'api_request_type', 'api_data', 'api_status_code', 'api_status_message', 'created_at'
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
