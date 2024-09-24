<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class CustomerQueryLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mongodb';
    protected $collection = 'customer_query_log';
    protected $fillable = [
        'name', 'email_id',
        'mobile_number',  'city', 'state', 'feedback', 'subject', 'api_source', 'api_source_page', 'api_type', 'api_header', 'api_url', 'api_request_type', 'api_data', 'api_status_code', 'api_status_message', 'created_at'
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
