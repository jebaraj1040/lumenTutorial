<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjDocument extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_document';
    protected $fillable = ['lead_id', 'quote_id', 'master_document_id', 'master_document_type_id', 'document_type_extension', 'document_saved_location', 'document_file_name', 'document_encrypted_name', 'document_position_id', 'created_at', 'updated_at'];

    public function document()
    {
        return $this->hasOne(HjMasterDocument::class, 'id', 'master_document_id');
    }
    public function documentType()
    {
        return $this->hasOne(HjMasterDocumentType::class, 'id', 'master_document_type_id');
    }
    public function leadDetail()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
}
