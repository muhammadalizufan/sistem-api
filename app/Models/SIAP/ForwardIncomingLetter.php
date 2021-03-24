<?php

namespace App\Models\SIAP;

use App\Models\Account\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForwardIncomingLetter extends Model
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
    protected $table = 'forward_incoming_letters';

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
        'incoming_letter_id',
        'user_id',
        'types',
        'comment',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'reciver_type',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    public function getReciverTypeAttribute()
    {
        switch ($this->attributes['types']) {
            case 0:
                return "Creator";
                break;
            case 1:
                return "Decision";
                break;
            case 2:
                return "Responder";
                break;
            case 3:
                return "Receiver";
                break;
            default:
                return null;
                break;
        }
    }

    public function IncomingLetter()
    {
        return $this->hasOne(IncomingLetter::class, "id", "incoming_letter_id")->with("Category");
    }

    public function User()
    {
        return $this->hasOne(User::class, "id", "user_id")->select('id', 'name');
    }

    public function Tags()
    {
        return $this->hasMany(TagIncomingLetter::class, "incoming_letter_id", "incoming_letter_id")->with("Tag");
    }
}
