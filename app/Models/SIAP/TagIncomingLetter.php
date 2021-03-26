<?php

namespace App\Models\SIAP;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagIncomingLetter extends Model
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
    protected $table = 'tag_incoming_letters';

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
        'tag_id',
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

    public function Tag()
    {
        return $this->hasOne(Tag::class, "id", "tag_id")->select('id', 'name');
    }

}
