<?php

namespace App\Models\Extension;

use App\Models\Account\User;
use App\Models\SIAP\OutgoingLetter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
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
    protected $table = 'activities';

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
        'ref_type',
        'ref_id',
        'action',
        'message_id',
        'message_en',
    ];

    /**
     * Custome appends attributes.
     *
     * @var array
     */
    protected $appends = [
        'reference_en',
        'reference_id',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'deleted_at',
    ];

    public function getReferenceEnAttribute()
    {
        switch ($this->attributes['ref_type']) {
            case 0:
                return "Default Activity";
                break;
            case 1:
                return "Disposition";
                break;
            case 2:
                return "Outgoing Letter";
                break;
            default:
                return null;
                break;
        }
    }

    public function getReferenceIdAttribute()
    {
        switch ($this->attributes['ref_type']) {
            case 0:
                return "Aktifitas Biasa";
                break;
            case 1:
                return "Surat Disposisi";
                break;
            case 2:
                return "Surat Keluar";
                break;
            default:
                return null;
                break;
        }
    }

    public function User()
    {
        return $this->hasOne(User::class, "id", "user_id")->with("Role")->select('id', 'name');
    }

    public function OutgoingLetter()
    {
        return $this->hasOne(OutgoingLetter::class, "id", "ref_id");
    }
}
