<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class UserSessionActivityLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'user_session_activity_log';
    protected $fillable = [
        'session_id', 'lead_id', 'quote_id',
        'browser_id', 'client_id', 'referer', 'expiry',
        'slug', 'ga_client_id', 'mobile_number', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'product_id', 'source', 'created_at'
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
