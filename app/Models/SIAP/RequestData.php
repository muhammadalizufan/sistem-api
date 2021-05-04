<?php

namespace App\Models\SIAP;

use App\Models\Account\User;
use App\Models\Extension\Category;
use App\Models\Extension\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestData extends Model
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
    protected $table = 'request_datas';

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
        'requested_data',
        'requester',
        'agency',
        'email',
        'phone',
        'file_original',
        'file_edited',
        'status',
        'is_archive',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'status_request',
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

    public function getStatusRequestAttribute()
    {
        if (is_null($this->attributes['status'] ?? null)) {
            return null;
        }

        switch ($this->attributes['status'] ?? "") {
            case 0:
                return "Process";
                break;
            case 1:
                return "Approved";
                break;
            case 2:
                return "Rejected";
                break;
            default:
                return null;
                break;
        }
    }

    public function Responders()
    {
        return $this->hasMany(ForwardRequestData::class, "request_data_id", "id")->where('types', 2);
    }

    public function FileOriginal()
    {
        return $this->hasOne(File::class, "fullname", "file_original")->select("id", "name", "fullname", 'created_at');
    }

    public function FileEdited()
    {
        return $this->hasOne(File::class, "fullname", "file_edited")->select("id", "name", "fullname", 'created_at');
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
