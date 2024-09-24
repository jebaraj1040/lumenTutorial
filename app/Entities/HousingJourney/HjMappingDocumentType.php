<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingDocumentType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    public $timestamps = false;
    protected $table = 'hj_mapping_document_type';
    protected $fillable = ['master_document_type_id', 'master_document_id'];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
