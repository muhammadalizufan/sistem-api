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
            if (!Permission::where(["name" => trim($p), "is_active" => 1])->exists()) {
                throw new \App\Exceptions\PermissionNotFoundException();
            }
        }
    }

    public function ErrorWhenUserExist(?object $User = null)
    {
        if (is_object($User)) {
            throw new \App\Exceptions\UserExistException();
        }
    }

    public function ErrorWhenUserNotFound(?object $User = null)
    {
        if (is_null($User)) {
            throw new \App\Exceptions\UserNotFoundException();
        }
    }

    public function ErrorWhenGroupExist(?object $Group = null)
    {
        if (is_object($Group)) {
            throw new \App\Exceptions\GroupExistException();
        }
    }

    public function ErrorWhenGroupNotFound(?object $Group = null)
    {
        if (is_null($Group)) {
            throw new \App\Exceptions\GroupNotFoundException();
        }
    }

    public function ErrorWhenRoleExist(?object $Role = null)
    {
        if (is_object($Role)) {
            throw new \App\Exceptions\RoleExistException();
        }
    }

    public function ErrorWhenRoleNotFound(?object $Role = null)
    {
        if (is_null($Role)) {
            throw new \App\Exceptions\RoleNotFoundException();
        }
    }
}
