<?php

namespace App\Entities\MongoLog;


use MongoDB\Laravel\Eloquent\Model;

class AuthLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'auth_log';
    protected $fillable = [
        'user_name',
        'user_id',
        'name',
        'role_handle',
        'command',
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
