<?php

namespace App\Entities\MongoLog;


use MongoDB\Laravel\Eloquent\Model;

class CaptchaLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'captcha_log';
    protected $fillable = [
        'request_id',
        'captcha',
        'is_expired',
        'created_at'
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
