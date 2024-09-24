<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterDocumentType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_document_type';
    protected $fillable = ['name', 'handle', 'is_active', 'created_at', 'updated_at'];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function documentDropDownList()
    {
        return $this->hasManyThrough(
            HjMasterDocument::class,
            HjMappingDocumentType::class,
            'master_document_type_id',
            'id',
            'id',
            'master_document_id'
        );
    }
}
