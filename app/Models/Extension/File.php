<?php

namespace App\Models\Extension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class File extends Model
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
    protected $table = 'files';

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
        'fullname',
        'ref_type',
        'ref_id',
        'ext',
        'path',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'url',
    ];

    public function getUrlAttribute()
    {
        return URL::to('/storage/' . $this->attributes['fullname']);
    }

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
}
