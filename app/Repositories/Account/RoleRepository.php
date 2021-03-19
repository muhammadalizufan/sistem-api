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

    public function GetRoleByName(Request $r): ?object
    {
        return Role::where('name', $r->name)->first();
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
        $R = Role::updateOrCreate([
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
                "permission_id" => Permission::where("name", trim($p))->first()->id ?? 0,
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

    public function EditRole(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed update group, body is empty",
            ];
        }

        // Convert Request Status
        $body = Helpers::ConvertStatusBody($body);

        // Update Role
        $R = $this->GetOneRole($body['role_id']);
        if (is_null($R)) {
            return [
                "status" => false,
                "message" => "failed update role, role id invalid",
            ];
        }
        $R->update([
            "name" => $body['name'],
            "is_active" => $body['status'],
        ]);

        // Get Permission By Role ID
        $RP = RolePermission::where([
            "role_id" => $R->id,
            "group_id" => $body['group_id'],
        ])->with("Permission")->get()->map(function ($i) {
            return $i['permission']['name'];
        });
        if ($RP->count() <= 0) {
            return [
                "status" => false,
                "message" => "failed update role",
            ];
        }
        $EqualPermission = $RP->intersect($body['permission']);
        $NotEqualPermission = $RP->diff($body['permission']);

        // Update
        foreach ($EqualPermission as $ep) {
            $Payload = [
                "role_id" => $R->id,
                "group_id" => $body['group_id'],
                "permission_id" => Permission::where("name", trim($ep))->first()->id,
            ];
            $RP = RolePermission::where($Payload);
            if (is_object($RP->first())) {
                $RP->update([
                    "is_active" => $body['status'],
                ]);
            }
        }

        if (count($NotEqualPermission) <= 0) {
            return [
                "status" => true,
            ];
        }

        // Non Activated Permission
        foreach ($NotEqualPermission as $nep) {
            $Payload = [
                "role_id" => $R->id,
                "group_id" => $body['group_id'],
                "permission_id" => Permission::where("name", trim($nep))->first()->id,
            ];
            $RP = RolePermission::where($Payload);
            if (is_object($RP->first())) {
                $RP->update([
                    "is_active" => 0,
                ]);
            }
        }

        return [
            "status" => true,
        ];
    }
}
