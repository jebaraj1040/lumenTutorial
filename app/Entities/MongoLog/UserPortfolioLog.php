<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class UserPortfolioLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mongodb';
    protected $collection = 'user_portfolio_log';
    protected $fillable = [
        'session_id', 'browser_id', 'mobile_number', 'product_id', 'lead_id',
        'quote_id', 'pan', 'source', 'created_at'
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
