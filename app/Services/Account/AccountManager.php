<?php
namespace App\Services\Account;

use App\Models\Account\Permission;

class AccountManager
{
    public function __construct()
    {
        //
    }

    public function CheckPermissionList(?array $Permissions = null)
    {
        if (is_null($Permissions)) {
            throw new \App\Exceptions\PermissionNotFoundException();
        }
        foreach ($Permissions as $key => $p) {
            if (!Permission::where("name", trim($p))->exists()) {
                throw new \App\Exceptions\PermissionNotFoundException();
            }
        }
    }

    public function IsUserExist(?object $User = null)
    {
        if (is_object($User)) {
            throw new \App\Exceptions\UserExistException();
        }
    }

    public function IsUserNotExist(?object $User = null)
    {
        if (is_null($User)) {
            throw new \App\Exceptions\UserNotFoundException();
        }
    }

    public function IsGroupExist(?object $Group = null)
    {
        if (is_null($Group)) {
            throw new \App\Exceptions\GroupNotFoundException();
        }
    }

    public function IsRoleExist(?object $Role = null)
    {
        if (is_null($Role)) {
            throw new \App\Exceptions\RoleNotFoundException();
        }
    }
}
