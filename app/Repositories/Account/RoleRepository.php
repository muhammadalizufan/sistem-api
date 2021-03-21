<?php
namespace App\Repositories\Account;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\Role;
use App\Models\Account\RolePermission;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait RoleRepository
{
    public function GetRole(int $id = 0): ?object
    {
        return Role::find($id);
    }

    public function GetRoleByName(Request $r, bool $IsNeedCompare = false): ?object
    {
        $R = Role::where('name', $r->name)->first();
        if ($IsNeedCompare) {
            return (strtolower($R->name) ?? "") == strtolower($r->name) ? null : $R;
        }
        return $R;
    }

    public function AddNewRole(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add role, body is empty",
            ];
        }

        // Convert Request Status
        $body = Helpers::ConvertStatusBody($body);

        // Update if Exists Or Create New Role
        $R = Role::create([
            'name' => $body['name'],
            "is_active" => $body['status'],
        ]);
        if (is_null($R)) {
            return [
                "status" => false,
                "message" => "failed add role",
            ];
        }

        // Check Body Permissions Payload
        $permissions = [];
        foreach ($body['permission'] as $p) {
            $Payload = [
                "group_id" => $body['group_id'],
                "role_id" => $R->id,
                "permission_id" => Permission::where(["name" => trim($p), "is_active" => 1])->first()->id ?? 0,
            ];
            if ($Payload['permission_id'] != 0 && !is_object(RolePermission::where($Payload)->first())) {
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
                "message" => "role permission already given",
            ];
        }

        // Insert MapPayload Permission
        $RP = RolePermission::insert($permissions);
        if (!$RP) {
            // Delete RoleID When Insert Permissions Error
            Role::where('id', $R->id)->forceDelete();
            return [
                "status" => false,
                "message" => "failed add role permission",
            ];
        }

        return [
            "status" => true,
        ];
    }

    // Update Issue
    public function EditRole(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed update role, body is empty",
            ];
        }

        // Convert Request Status
        $body = Helpers::ConvertStatusBody($body);

        // Update Role
        $R = $this->GetRole($body['role_id']);
        if (is_null($R)) {
            return [
                "status" => false,
                "message" => "failed update role",
            ];
        }
        $R->update([
            "name" => $body['name'],
            "group_id" => $body['group_id'],
            "is_active" => $body['status'],
        ]);

        // Get Permission By Group ID
        $RP = RolePermission::where("role_id", $body['role_id'])->with("Permission")->get()
            ->map(function ($i) {
                return $i['permission']['name'];
            });
        $Raw = $RP->toArray();
        $GetPermissionID = function (string $name = ""): int {
            return Permission::where(["name" => trim($name), "is_active" => 1])->first()->id ?? 0;
        };

        // Update
        $PermissionWillUpdate = collect($Raw)->intersect($body['permission']);
        foreach ($PermissionWillUpdate->toArray() as $p) {
            $Query = [
                "role_id" => $R->id,
                "permission_id" => $GetPermissionID($p),
            ];
            $GP = RolePermission::where($Query);
            if (is_object($GP)) {
                $GP->update([
                    "group_id" => $body['group_id'],
                    "is_active" => $body['status'],
                ]);
            }
        }

        // Insert Or Restore
        $PermissionWillInsert = collect($body['permission'])->diff($Raw);
        foreach ($PermissionWillInsert->toArray() as $p) {
            $PermissionID = $GetPermissionID($p);
            if ($PermissionID != 0) {
                $Query = [
                    "role_id" => $R->id,
                    "permission_id" => $PermissionID,
                ];
                $GP = RolePermission::onlyTrashed()->where($Query);
                if (is_object($GP->first())) {
                    $GP->update([
                        "group_id" => $body['group_id'],
                        "is_active" => $body['status'],
                    ]);
                    $GP->restore();
                } else {
                    RolePermission::create(array_merge($Query, [
                        "group_id" => $body['group_id'],
                        "is_active" => $body['status'],
                    ]));
                }
            }
        }

        // Delete
        $PermissionWillDelete = collect($Raw)->diff($PermissionWillInsert->merge($PermissionWillUpdate));
        foreach ($PermissionWillDelete->toArray() as $p) {
            $PermissionID = $GetPermissionID($p);
            if ($PermissionID != 0) {
                RolePermission::where([
                    "role_id" => $R->id,
                    "permission_id" => $PermissionID,
                ])->delete();
            }
        }

        return [
            "status" => true,
        ];
    }
}
