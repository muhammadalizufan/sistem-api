<?php
namespace App\Repositories\Account;

use App\Models\Account\Group;
use App\Models\Account\Permission;
use App\Models\Account\Role;
use App\Models\Account\User;
use App\Models\Account\UserPermission;
use App\Models\Account\UserRefreshToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait UserRepository
{
    public function GetUserByEmail(Request $r)
    {
        return User::where('email', $r->email)->with("Roles")->first();
    }

    public function GetUserPermissionByUserID(int $id = 0)
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

    public function CreateUserRefreshToken(Request $r, $User, $RefreshToken): object
    {
        return UserRefreshToken::updateOrCreate([
            "user_id" => $User->id,
            "user_agent" => $r->server("HTTP_USER_AGENT"),
            "refresh_token" => $RefreshToken,
        ]);
    }

    public function AddNewUser(array $body = []): array
    {
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add user, body is empty",
            ];
        }

        if (is_object(User::where('email', $body['email'])->first())) {
            return [
                "status" => false,
                "message" => "failed add user, email already used",
            ];
        }

        switch (strtolower($body['status'] ?? "")) {
            case 'active':
                $body['status'] = 1;
                break;
            case 'inactive':
                $body['status'] = 0;
                break;
            default:
                $body['status'] = 0;
                break;
        }

        // Update if Exists Or Create New User
        $U = User::updateOrCreate([
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

        // Check Group Is Valid
        $G = Group::where(["id" => $body['group_id']])->first();
        if (!is_object($G)) {
            User::where("id", $U->id)->forceDelete();
            return [
                "status" => false,
                "message" => "failed add user, group not found",
            ];
        }

        // Check Role Is Valid
        $R = Role::where(["id" => $body['role_id']])->first();
        if (!is_object($R)) {
            User::where("id", $U->id)->forceDelete();
            return [
                "status" => false,
                "message" => "failed add user, role not found",
            ];
        }

        // Check Body Permissions Payload
        $permissions = [];
        foreach ($body['permission'] as $p) {
            $Payload = [
                "user_id" => $U->id,
                "group_id" => $G->id,
                "role_id" => $R->id,
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
            if (!is_object(UserPermission::where([
                'user_id' => $U->id,
                'group_id' => $G->id,
                'role_id' => $R->id,
            ])->first())) {
                User::where("id", $U->id)->forceDelete();
            }
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
}
