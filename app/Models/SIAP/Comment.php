<?php

namespace App\Models\SIAP;

use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    /**
     * Specific Connection.
     *
     * @var Connection
     */
    protected $connection = 'siap';

    /**
     * Specific Table Name.
     *
     * @var TableName
     */
    protected $table = 'comments';

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
        'ref_id',
        'ref_type',
        'created_by',
        'comment',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    public function Disposition()
    {
        return $this->hasOne(Inbox::class, "ref_id", "ref_id")->where("ref_type", "Disposition");
    }

    public function RequestData()
    {
        return $this->hasOne(Inbox::class, "ref_id", "ref_id")->where("ref_type", "RequestData");
    }

    public function User()
    {
        return $this->hasOne(User::class, "id", "created_by")->select('id', 'name')->with("Role");
    }
}
