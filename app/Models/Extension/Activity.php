<?php

namespace App\Models\Extension;

use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    /**
     * Specific Connection.
     *
     * @var Connection
     */
    protected $connection = 'extension';

    /**
     * Specific Table Name.
     *
     * @var TableName
     */
    protected $table = 'activities';

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
        'user_id',
        'ref_type',
        'ref_id',
        'action',
        'message_id',
        'message_en',
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

    public function User()
    {
        return $this->hasOne(User::class, "id", "user_id")->select('id', 'name');
    }
}
