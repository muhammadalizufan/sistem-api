<?php
namespace App\Repositories\Account;

use App\Libs\Helpers;
use App\Models\Account\Group;
use App\Models\Account\GroupPermission;
use App\Models\Account\Permission;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait GroupRepository
{
    public function GetGroup(int $id = 0): ?object
    {
        return Group::find($id);
    }

    public function GetGroupByName(Request $r, bool $IsNeedCompare = false): ?object
    {
        $G = Group::where('name', $r->name)->first();
        if ($IsNeedCompare) {
            return ($G->name ?? "") == $r->name ? null : $G;
        }
        return $G;
    }

    public function AddNewGroup(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add group, body is empty",
            ];
        }

        // Convert Request Status Text
        $body = Helpers::ConvertStatusBody($body);

        // Update if Exists Or Create New Group
        $G = Group::create([
            'name' => $body['name'],
            "is_active" => $body['status'],
        ]);
        if (is_null($G)) {
            return [
                "status" => false,
                "message" => "failed add group",
            ];
        }

        // Check Body Permissions Payload
        $permissions = [];
        foreach ($body['permission'] as $p) {
            $Payload = [
                "group_id" => $G->id,
                "permission_id" => Permission::where(["name" => trim($p), "is_active" => 1])->first()->id ?? 0,
            ];
            if ($Payload['permission_id'] != 0 && !is_object(GroupPermission::where($Payload)->first())) {
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
                "message" => "group permission already given",
            ];
        }

        // Insert MapPayload Permission
        $GP = GroupPermission::insert($permissions);
        if (!$GP) {
            // Delete GroupID When Insert Permissions Error
            Group::where('id', $G->id)->forceDelete();
            return [
                "status" => false,
                "message" => "failed add group permission",
            ];
        }

        return [
            "status" => true,
        ];
    }

    public function EditGroup(array $body = []): array
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

        // Update Group
        $G = $this->GetGroup($body['group_id']);
        if (is_null($G)) {
            return [
                "status" => false,
                "message" => "failed update group",
            ];
        }
        $G->update([
            "name" => $body['name'],
            "is_active" => $body['status'],
        ]);

        // Get Permission By Group ID
        $GP = GroupPermission::where("group_id", $body['group_id'])->with("Permission")->get()
            ->map(function ($i) {
                return $i['permission']['name'];
            });
        if ($GP->count() <= 0) {
            return [
                "status" => false,
                "message" => "failed update group",
            ];
        }
        $Raw = $GP->toArray();

        $GetPermissionID = function (string $name = ""): int {
            return Permission::where(["name" => trim($name), "is_active" => 1])->first()->id ?? 0;
        };

        // Update
        $PermissionWillUpdate = collect($Raw)->intersect($body['permission']);
        foreach ($PermissionWillUpdate->toArray() as $p) {
            $Query = [
                "group_id" => $G->id,
                "permission_id" => $GetPermissionID($p),
            ];
            $GP = GroupPermission::where($Query);
            if (is_object($GP)) {
                $GP->update([
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
                    "group_id" => $G->id,
                    "permission_id" => $PermissionID,
                ];
                $GP = GroupPermission::withTrashed()->where($Query);
                if (is_object($GP->first())) {
                    $GP->restore();
                    $GP->update([
                        "is_active" => $body['status'],
                    ]);
                } else {
                    GroupPermission::create(array_merge($Query, ["is_active" => $body['status']]));
                }
            }
        }

        // Delete
        $PermissionWillDelete = collect($Raw)->diff($PermissionWillInsert->merge($PermissionWillUpdate));
        foreach ($PermissionWillDelete->toArray() as $p) {
            $PermissionID = $GetPermissionID($p);
            if ($PermissionID != 0) {
                GroupPermission::where([
                    "group_id" => $G->id,
                    "permission_id" => $PermissionID,
                ])->delete();
            }
        }

        return [
            "status" => true,
        ];
    }
}
