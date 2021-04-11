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

    public function getTagsAttribute()
    {
        return Tag::whereIn('id', explode(',', $this->attributes['tags'] ?? ""))->get()->toArray() ?? null;
    }

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

    public function File()
    {
        return $this->hasOne(File::class, "id", "file_id")->select("id", "name", "fullname", "created_at", "updated_at");
    }

    public function Category()
    {
        return $this->hasOne(Category::class, "id", "cat_id")->select('id', 'name');
    }
}
