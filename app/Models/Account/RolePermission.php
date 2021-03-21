<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
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
    protected $table = 'role_permissions';

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
        'group_id',
        'role_id',
        'permission_id',
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

    public function Group()
    {
        return $this->hasOne(Group::class, "id", "group_id")->select("id", "name");
    }

    public function Role()
    {
        return $this->hasOne(Role::class, "id", "role_id");
    }

    public function Permission()
    {
        return $this->hasOne(Permission::class, "id", "permission_id")->select("id", "name");
    }
}
