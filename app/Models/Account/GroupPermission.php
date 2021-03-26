<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupPermission extends Model
{
    /**
     * Specific Connection.
     *
     * @var Connection
     */
    protected $connection = 'account';

    /**
     * Specific Table Name.
     *
     * @var TableName
     */
    protected $table = 'group_permissions';

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

    public function Permission()
    {
        return $this->hasOne(Permission::class, "id", "permission_id")->select("id", "name");
    }
}
