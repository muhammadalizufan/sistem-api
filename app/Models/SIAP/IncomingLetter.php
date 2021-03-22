<?php

namespace App\Models\SIAP;

use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingLetter extends Model
{
    /**
     * Specific Connection.
     *
     * @var TableName
     */
    protected $connection = 'siap';

    /**
     * Specific Table Name.
     *
     * @var TableName
     */
    protected $table = 'incoming_letters';

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
        'cat_id',
        'code',
        'title',
        'from',
        'date',
        'dateline',
        'file',
        'desc',
        'note',
        'status',
        'is_archive',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'status_letter',
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

    public function getStatusLetterAttribute()
    {
        switch ($this->attributes['status']) {
            case 0:
                return "Process";
                break;
            case 1:
                return "Success";
                break;
            case 2:
                return "Failed";
                break;
            default:
                return null;
                break;
        }
    }

    public function Category()
    {
        return $this->hasOne(Category::class, "id", "cat_id")->select('id', 'name');
    }

    public function User()
    {
        return $this->hasOne(User::class, "id", "user_id")->select('id', 'name');
    }
}
