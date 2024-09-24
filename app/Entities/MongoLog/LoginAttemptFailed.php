<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class LoginAttemptFailed extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mongodb';
    protected $table = 'login_attempt_failed';
    protected $fillable = [
        'user_name',
        'count',
        'created_at',
        'updated_at',
    ];
}
