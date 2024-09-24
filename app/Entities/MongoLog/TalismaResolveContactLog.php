<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class TalismaResolveContactLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'talisma_resolve_contact_log';
    protected $fillable = [
        'mobile_number', 'api_source', 'api_source_page',
        'api_type', 'api_url', 'api_request_type',
        'api_data', 'api_status_code', 'api_status_message', 'created_at'
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
