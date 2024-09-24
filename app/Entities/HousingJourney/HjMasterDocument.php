<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterDocument extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_document';
    protected $fillable = [
        'name', 'handle', 'master_id',
        'max_file', 'max_size_per_file_mb',
        'allowed_extensions', 'max_duration_type', 'max_duration', 'is_active', 'created_at', 'updated_at'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
