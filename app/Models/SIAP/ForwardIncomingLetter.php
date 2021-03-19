<?php

namespace App\Models\SIAP;

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
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function IncomingLetter()
    {
        return $this->hasOne(IncomingLetter::class, "id", "incoming_letter_id");
    }

    public function User()
    {
        return $this->hasOne(User::class, "id", "user_id");
    }
}
