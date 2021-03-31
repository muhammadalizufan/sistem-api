<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\Account\Group;
use App\Models\Account\GroupPermission;
use App\Models\Account\Permission;
use App\Models\Account\Role;
use App\Models\Account\RolePermission;
use App\Models\Account\User;
use App\Models\Account\UserPermission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function AddTestDummy(Request $r)
    {
        $Array = [
            [
                "group" => [
                    "name" => "Super Admin",
                    "permission" => [
                        "SIAP.Disposition",
                        "Setting.GroupManagement",
                        "Setting.UserManagement",
                        "Setting.UserProfile",
                    ],
                ],
                "role" => [
                    "name" => "Super Admin",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Decision",
                        "SIAP.Disposition.Responders",
                        "SIAP.Disposition.Send",
                        "Setting.GroupManagement",
                        "Setting.GroupManagement.ViewSearch",
                        "Setting.GroupManagement.ViewDetail",
                        "Setting.GroupManagement.Add",
                        "Setting.GroupManagement.Edit",
                        "Setting.UserManagement",
                        "Setting.UserManagement.Member.ViewSearch",
                        "Setting.UserManagement.Member.ViewDetail",
                        "Setting.UserManagement.Member.Add",
                        "Setting.UserManagement.Member.Edit",
                        "Setting.UserManagement.Role.ViewSearch",
                        "Setting.UserManagement.Role.ViewDetail",
                        "Setting.UserManagement.Role.Add",
                        "Setting.UserManagement.Role.Edit",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
                "user" => [
                    "name" => "Super Admin",
                    "email" => "admin@mail.com",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Send",
                        "Setting.GroupManagement",
                        "Setting.GroupManagement.ViewSearch",
                        "Setting.GroupManagement.ViewDetail",
                        "Setting.GroupManagement.Add",
                        "Setting.GroupManagement.Edit",
                        "Setting.UserManagement",
                        "Setting.UserManagement.Member.ViewSearch",
                        "Setting.UserManagement.Member.ViewDetail",
                        "Setting.UserManagement.Member.Add",
                        "Setting.UserManagement.Member.Edit",
                        "Setting.UserManagement.Role.ViewSearch",
                        "Setting.UserManagement.Role.ViewDetail",
                        "Setting.UserManagement.Role.Add",
                        "Setting.UserManagement.Role.Edit",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
            ],
            [
                "group" => [
                    "name" => "Ketua",
                    "permission" => [
                        "SIAP.Disposition",
                        "Setting.UserProfile",
                    ],
                ],
                "role" => [
                    "name" => "Ketua",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Decision",
                        "SIAP.Disposition.Responders",
                        "SIAP.Disposition.Send",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
                "user" => [
                    "name" => "Ketua 1",
                    "email" => "ketuasatu@mail.com",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Decision",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
            ],
            [
                "group" => [
                    "name" => "Sekretaris",
                    "permission" => [
                        "SIAP.Disposition",
                        "Setting.UserProfile",
                    ],
                ],
                "role" => [
                    "name" => "Sekretaris Eksekutif",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Responders",
                        "SIAP.Disposition.Send",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
                "user" => [
                    "name" => "User Sekretaris Eksekutif",
                    "email" => "se@mail.com",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Send",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
            ],
            [
                "group" => [
                    "name" => "Manajer",
                    "permission" => [
                        "SIAP.Disposition",
                        "Setting.UserProfile",
                    ],
                ],
                "role" => [
                    "name" => "Manajer Umum",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Decision",
                        "SIAP.Disposition.Responders",
                        "SIAP.Disposition.Send",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
                "user" => [
                    "name" => "User Manajer Umum",
                    "email" => "mu@mail.com",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Responders",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
            ],
            [
                "group" => [
                    "name" => "Kabag",
                    "permission" => [
                        "SIAP.Disposition",
                        "Setting.UserProfile",
                    ],
                ],
                "role" => [
                    "name" => "Kabag Produksi",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Decision",
                        "SIAP.Disposition.Responders",
                        "SIAP.Disposition.Send",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
                "user" => [
                    "name" => "User Kabag Produksi",
                    "email" => "kprod@mail.com",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Responders",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
            ],
            [
                "group" => [
                    "name" => "Kabag",
                ],
                "role" => [
                    "name" => "Kabag Keuangan",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.1",
                        "SIAP.Disposition.Level.2",
                        "SIAP.Disposition.Level.3",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Add",
                        "SIAP.Disposition.Edit",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Decision",
                        "SIAP.Disposition.Responders",
                        "SIAP.Disposition.Send",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
                "user" => [
                    "name" => "User Kabag Keuangan",
                    "email" => "kuang@mail.com",
                    "permission" => [
                        "SIAP.Disposition",
                        "SIAP.Disposition.Level.4",
                        "SIAP.Disposition.ViewSearch",
                        "SIAP.Disposition.ViewDetail",
                        "SIAP.Disposition.Comment",
                        "SIAP.Disposition.Responders",
                        "Setting.UserProfile",
                        "Setting.UserProfile.View",
                        "Setting.UserProfile.Edit",
                        "Setting.UserProfile.ChangePassword",
                    ],
                ],
            ],
        ];

        foreach ($Array as $k => $v) {
            $G = Group::updateOrcreate([
                'name' => $v['group']['name'],
                'is_active' => 1,
            ]);
            $Permissions = [];
            foreach ($v['group']['permission'] as $p) {
                \array_push($Permissions, [
                    'group_id' => $G->id,
                    'permission_id' => Permission::where(['name' => $p, 'is_active' => 1])->first()->id ?? 0,
                    'is_active' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            GroupPermission::insert($Permissions);

            $R = Role::updateOrcreate([
                'name' => $v['role']['name'],
                'is_active' => 1,
            ]);
            $Permissions = [];
            foreach ($v['role']['permission'] as $p) {
                \array_push($Permissions, [
                    'group_id' => $G->id,
                    'role_id' => $R->id,
                    'permission_id' => Permission::where(['name' => $p, 'is_active' => 1])->first()->id ?? 0,
                    'is_active' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            RolePermission::insert($Permissions);

            $U = User::updateOrcreate([
                'name' => $v['user']['name'],
                'email' => $v['user']['email'],
                'password' => Hash::make("Rahasia123&", [
                    'rounds' => 10,
                ]),
                'status' => 1,
            ]);
            $Permissions = [];
            foreach ($v['user']['permission'] as $p) {
                \array_push($Permissions, [
                    'user_id' => $U->id,
                    'role_id' => $R->id,
                    'group_id' => $G->id,
                    'permission_id' => Permission::where(['name' => $p, 'is_active' => 1])->first()->id ?? 0,
                    'is_active' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            UserPermission::insert($Permissions);
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add test user dummy",
        ], 201);
    }
}
