<?php
namespace App\Entities\Crm;
use Illuminate\Database\Eloquent\Model;
class RoleMenuMapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'role_menu_mapping';
    protected $fillable = [
        'role_id',
        'menu_id',
        'is_active',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
    public function menu()
    {
        return $this->hasMany(MenuMaster::class, 'id', 'menu_id');
    }
    public function userMenu()
    {
        return $this->belongsTo(UserMenuMapping::class, 'menu_id', 'menu_id');
    }
    public function roleName()
    {
        return $this->hasOne(RoleMaster::class, 'id', 'role_id');
    }
}
