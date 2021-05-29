<?php

namespace App\Models\SIAP;

use App\Models\Extension\Category;
use App\Models\Extension\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingLetter extends Model
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
        'file_id',
        'desc',
        'note',
        'tags',
        'private',
        'status',
        'is_archive',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'tags',
        'status_letter',
        'dateline_type',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    public function getDatelineTypeAttribute()
    {
        if (is_null($this->attributes['created_at'] ?? null) || is_null($this->attributes['dateline'] ?? null)) {
            return null;
        }
        switch (date_diff(date_create($this->attributes['created_at']), date_create($this->attributes['dateline']))->d) {
            case 1:
                return "OneDay";
                break;
            case 2:
                return "TwoDay";
                break;
            case 3:
                return "ThreeDay";
                break;
            default:
                return null;
                break;
        }
    }

    public function getTagsAttribute()
    {
        if (is_null($this->attributes['tags'] ?? null)) {
            return null;
        }
        return Tag::whereIn('id', explode(',', $this->attributes['tags'] ?? ""))->get()->toArray() ?? null;
    }

    public function getStatusLetterAttribute()
    {
        if (is_null($this->attributes['status'] ?? null)) {
            return null;
        }
        switch ($this->attributes['status'] ?? null) {
            case 0:
                return "Process";
                break;
            case 1:
                return "Done";
                break;
                break;
            default:
                return null;
                break;
        }
    }

    public function File()
    {
        return $this->hasMany(FileIncomingLetter::class, "incoming_letter_id", "id")->with("File");
    }

    public function Category()
    {
        return $this->hasOne(Category::class, "id", "cat_id")->select('id', 'name');
    }
}
