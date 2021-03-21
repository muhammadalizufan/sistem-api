<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    /**
     * Specific Connection.
     *
     * @var TableName
     */
    protected $connection = 'account';

    /**
     * Specific Table Name.
     *
     * @var TableName
     */
    protected $table = 'roles';

    /**
     * Activate a Soft Delete.
     *
     * @package SoftDeletes
     */
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getNameAttribute($name)
    {
        return ucwords(strtolower(str_replace('_', ' ', $name)));
    }

    public function Group()
    {
        return $this->hasOne(RolePermission::class, "role_id", "id")->with("Group");
    }

    public function Permissions()
    {
        return $this->hasMany(RolePermission::class, "role_id", "id")->with("Permission")->select("role_id", "permission_id");
    }
}
