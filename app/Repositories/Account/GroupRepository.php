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

    public function GetGroupByName(Request $r): ?object
    {
        return Group::where('name', $r->name)->first();
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
        $G = Group::updateOrCreate([
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
        $G = Group::find($body['group_id']);
        if (is_null($G)) {
            return [
                "status" => false,
                "message" => "failed update group",
            ];
        }
        $G->update([
            "is_active" => $body['status'],
        ]);

        // Get Permission By Group ID
        $GP = GroupPermission::where("group_id", $G->id)->with("Permission")->get()->map(function ($i) {
            return $i['permission']['name'];
        });
        if ($GP->count() <= 0) {
            return [
                "status" => false,
                "message" => "failed update group",
            ];
        }
        $EqualPermission = $GP->intersect($body['permission']);
        $NotEqualPermission = $GP->diff($body['permission']);

        // Update
        foreach ($EqualPermission as $ep) {
            $Payload = [
                "group_id" => $G->id,
                "permission_id" => Permission::where("name", trim($ep))->first()->id,
            ];
            $GP = GroupPermission::where($Payload);
            if (is_object($GP->first())) {
                $GP->update([
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
                "group_id" => $G->id,
                "permission_id" => Permission::where("name", trim($nep))->first()->id,
            ];
            $GP = GroupPermission::where($Payload);
            if (is_object($GP->first())) {
                $GP->update([
                    "is_active" => 0,
                ]);
            }
        }

        return [
            "status" => true,
        ];
    }
}
