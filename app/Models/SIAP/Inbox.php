<?php

namespace App\Models\SIAP;

use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inbox extends Model
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
    protected $table = 'inboxs';

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
        'forward_to',
        'user_type',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'types',
    ];

    public function getTypesAttribute()
    {
        return explode(",", $this->attributes['user_type']) ?? [];
    }

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
        return $this->hasOne(IncomingLetter::class, "id", "ref_id")->with("Category", "File");
    }

    public function User()
    {
        return $this->hasOne(User::class, "id", "forward_to")->select('id', 'name')->with("Role");
    }
}
