<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Model;

class UserRefreshToken extends Model
{
    /**
     * Specific Connection.
     *
     * @var TableName
     */
    protected $connection = 'account';

    /**
     * Specific Table Name.
     *
     * @var TableName
     */
    protected $table = 'user_refresh_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "user_id",
        "user_agent",
        "refresh_token",
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

    public function User()
    {
        return $this->hasOne(User::class, "id", "user_id");
    }
}
