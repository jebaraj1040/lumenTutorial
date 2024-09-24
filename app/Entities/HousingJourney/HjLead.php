<?php

namespace App\Entities\HousingJourney;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class HjLead extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_lead';
    protected $fillable = [
        'name', 'mobile_number', 'email',
        'is_applicant', 'pincode_id',
        'is_being_assisted', 'is_otp_verified',
        'partner_code', 'partner_name', 'home_extension',
        'sub_partner_code', 'is_agreed', 'customer_type', 'created_at', 'updated_at'
    ];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function pincodeData()
    {
        return $this->hasOne(HjMasterPincode::class, 'id', 'pincode_id');
    }
}
