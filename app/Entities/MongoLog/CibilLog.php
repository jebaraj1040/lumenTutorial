<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class CibilLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mongodb';
    protected $collection = 'cibil_log';
    protected $fillable = [
        'lead_id', 'quote_id', 'mobile_number', 'master_product_id', 'pan',
        'api_source', 'api_source_page', 'api_type', 'api_header',
        'api_url', 'api_request_type','cibil_from','api_status_code','api_response','api_request',
        'api_status_message', 'created_at'
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
