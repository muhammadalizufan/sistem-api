<?php

namespace App\Models\Account;

use App\Models\Account\UserPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
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
    protected $table = 'users';

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
        'username',
        'email',
        'password',
        'pin',
        'access_token',
        'use_twofa',
        'status',
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
        return $this->hasOne(UserPermission::class, "user_id", "id")
            ->select("user_id", "group_id")
            ->distinct("group_id")
            ->with("Group");
    }

    public function Role()
    {
        return $this->hasOne(UserPermission::class, "user_id", "id")
            ->select("user_id", "role_id")
            ->distinct("role_id")
            ->with("Role");
    }

    public function Permissions()
    {
        return $this->hasMany(UserPermission::class, "user_id", "id")->with("Permission")->select("user_id", "permission_id");
    }

}
