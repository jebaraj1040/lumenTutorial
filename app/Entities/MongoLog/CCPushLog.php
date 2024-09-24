<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class CCPushLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'cc_push_log';
    protected $fillable = [
        'lead_id', 'quote_id', 'cc_quote_id', 'mobile_number', 'master_product_id', 'api_source', 'api_source_page', 'api_type', 'api_header', 'api_url', 'api_request_type', 'api_data',
        'cc_push_stage_id', 'cc_push_sub_stage_id', 'cc_push_sub_stage_priority', 'api_status_code', 'api_status_message', 'response', 'created_at'
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
