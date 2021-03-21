<?php
namespace App\Repositories\Account;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\RefreshToken;
use App\Models\Account\User;
use App\Models\Account\UserPermission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait UserRepository
{
    public function GetUser(int $id = 0): ?object
    {
        return User::find($id);
    }

    public function GetUserByEmail(Request $r): ?object
    {
        return User::where('email', $r->email)->with("Group", "Role")->first();
    }

    public function GetUserPermissionByUserID(int $id = 0): ?object
    {
        return UserPermission::where('user_id', $id)
            ->with("Permission")->get()
            ->map(function ($i) {
                return $i['permission']['name'] ?? null;
            })
            ->filter(function ($v) {
                return !is_null($v);
            });
    }

    public function CreateRefreshToken(Request $r, $User, $RefreshToken): ?object
    {
        return RefreshToken::updateOrCreate([
            "user_id" => $User->id,
            "user_agent" => $r->server("HTTP_USER_AGENT"),
            "refresh_token" => $RefreshToken,
        ]);
    }

    public function GetRefreshToken(?string $RefreshToken = null): ?object
    {
        return RefreshToken::where('refresh_token', $RefreshToken)->with('User')->first();
    }

    public function AddNewUser(array $body = []): array
    {
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add user, body is empty",
            ];
        }

        // Convert Request Status
        $body = Helpers::ConvertStatusBody($body);

        // Update if Exists Or Create New User
        $U = User::create([
            "name" => $body['name'],
            "email" => $body['email'],
            "password" => Hash::make("Rahasia123&", [
                'rounds' => 10,
            ]),
            "status" => $body['status'],
        ]);
        if (!is_object($U)) {
            return [
                "status" => false,
                "message" => "failed add user",
            ];
        }

        // Check Body Permissions Payload
        $permissions = [];
        foreach ($body['permission'] as $p) {
            $Payload = [
                "user_id" => $U->id,
                "group_id" => $body['group_id'],
                "role_id" => $body['role_id'],
                "permission_id" => Permission::where(["name" => trim($p), "is_active" => 1])->first()->id ?? 0,
            ];
            if ($Payload['permission_id'] != 0 && !is_object(UserPermission::where($Payload)->first())) {
                array_push($permissions, array_merge($Payload, [
                    "is_active" => $body['status'],
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]));
            }
        }
        if (count($permissions) <= 0) {
            return [
                "status" => false,
                "message" => "user permission already given",
            ];
        }

        // Insert MapPayload Permission
        $UP = UserPermission::insert($permissions);
        if (!$UP) {
            User::where("id", $U->id)->forceDelete();
            return [
                "status" => false,
                "message" => "failed add user permission",
            ];
        }
        return [
            "status" => true,
        ];
    }

    public function EditUser(array $body = []): array
    {
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed edit user, body is empty",
            ];
        }

        // Convert Request Status
        $body = Helpers::ConvertStatusBody($body);

        // Update
        $U = User::find($body['user_id']);
        $U->update([
            "name" => $body['name'],
            "email" => $body['email'],
            "status" => $body['status'],
        ]);
        if (!is_object($U)) {
            return [
                "status" => false,
                "message" => "failed edit user",
            ];
        }

        // Delete Old Permission
        $UP = UserPermission::where([
            "user_id" => $body['user_id'],
        ]);
        $UP->delete();

        // Check Body Permissions Payload
        $permissions = [];
        foreach ($body['permission'] as $p) {
            $Payload = [
                "user_id" => $U->id,
                "group_id" => $body['group_id'],
                "role_id" => $body['role_id'],
                "permission_id" => Permission::where(["name" => trim($p), "is_active" => 1])->first()->id ?? 0,
            ];
            if ($Payload['permission_id'] != 0 && !is_object(UserPermission::where($Payload)->first())) {
                array_push($permissions, array_merge($Payload, [
                    "is_active" => $body['status'],
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]));
            }
        }
        if (count($permissions) <= 0) {
            return [
                "status" => false,
                "message" => "user permission already given",
            ];
        }
        $UP = UserPermission::insert($permissions);
        if (!$UP) {
            return [
                "status" => false,
                "message" => "failed add user permission",
            ];
        }
        return [
            "status" => true,
        ];
    }

    public function ChangeUserPassword(array $body = []): array
    {
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed change user password, body is empty",
            ];
        }
        $U = User::where('email', $body['email'])->update([
            'password' => Hash::make($body['new_password'], [
                'rounds' => 10,
            ]),
        ]);
        if (!$U) {
            return [
                "status" => false,
                "message" => "failed change user password",
            ];
        }
        return [
            "status" => true,
        ];
    }
}
