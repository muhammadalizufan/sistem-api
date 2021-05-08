<?php
namespace App\Repositories\SIAP;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\Role;
use App\Models\Account\User;
use App\Models\Account\UserPermission;
use App\Models\Extension\Category;
use App\Models\Extension\File;
use App\Models\SIAP\Comment;
use App\Models\SIAP\FileIncomingLetter;
use App\Models\SIAP\Inbox;
use App\Models\SIAP\IncomingLetter;
use App\Models\SIAP\Tag;
use App\Repositories\Extension\ExtensionRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait DispositionRepository
{
    public function AddNewLetter(Request $r)
    {
        $C = Category::updateOrcreate([
            'name' => trim($r->cat_name),
        ]);

        Helpers::ConvertDatelineBodyToDate($r);

        $Tags = collect([]);
        if (count($r->tags ?? []) > 0) {
            foreach ($r->tags as $t) {
                if (strlen($t) != 0) {
                    $Tags->push(Tag::updateOrcreate([
                        "name" => trim($t),
                    ])->id);
                }
            }
        }

        $Data = array_merge(
            $r->all([
                'title',
                'from',
                'desc',
                'dateline',
                'note',
                'private',
            ]), [
                'user_id' => $r->UserData->id ?? 0,
                'cat_id' => $C->id ?? 0,
                'code' => "SIAP/SM/" . time(),
                'tags' => implode(",", $Tags->toArray()),
                'date' => Carbon::now(),
                'status' => 0, // Process
                'is_archive' => 0,
            ]
        );

        $F = File::whereIn('id', $r->input('file', []));
        $Files = $F->get();
        if (!is_object($Files)) {
            return false;
        }

        $IL = IncomingLetter::create($Data);
        if (!is_object($IL)) {
            return false;
        }

        $AddDate = function (array $array = []) {
            return array_merge($array, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        };

        if (is_object($Files)) {
            $F->update([
                'ref_type' => "Disposition",
                'ref_id' => $IL->id,
                'is_used' => 1,
            ]);
            FileIncomingLetter::insert(
                $Files->map(function ($i) use ($IL, $AddDate) {
                    return $AddDate([
                        "incoming_letter_id" => $IL->id,
                        "file_id" => $i['id'],
                    ]);
                })->toArray(),
            );
        }

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id ?? 0,
            'ref_type' => "Disposition",
            'ref_id' => $IL->id,
            'action' => "Add",
            'message_id' => "Menambahkan surat disposisi baru",
            'message_en' => "Adding a new disposition letter",
        ]);

        $SPVs = [];
        if (!$r->private) {
            $PIDs = Permission::whereIn("name", [
                "SIAP.Disposition.Level.A",
                "SIAP.Disposition.Level.B",
            ])->get()->map(function ($i) {
                return $i['id'];
            })->toArray();
            $UPs = UserPermission::whereIn("permission_id", $PIDs)->where("is_active", 1);
            $SPVs = $UPs->get()->map(function ($i) {
                return $i['user_id'];
            })->toArray();
        }
        $Data = collect([]);

        $UAdmin = UserPermission::where(
            "permission_id",
            Permission::where("name", "SIAP.Disposition.Level.Z")->first()->id ?? 0
        )->where("is_active", 1)->get()->map(function ($i) {
            return $i['user_id'];
        })->toArray();

        $Responders = $r->user_responders ?? [];
        $Supervisors = array_merge($r->user_supervisors ?? [], $SPVs ?? []);
        $Admins = array_merge($Admins ?? [], [$r->UserData->id]);

        foreach (array_unique(array_merge($Responders, $Supervisors, $SPVs, $Admins, [$r->user_decision])) as $uid) {
            $Type = ["Administator", "Decision", "Responder", "Supervisor"];

            $UTypeArr = collect([]);
            if (in_array($uid, $UAdmin)) {
                $UTypeArr->push($Type[0]);
            }

            if ($uid == $r->user_decision) {
                $UTypeArr->push($Type[1]);
            }

            if (in_array($uid, $Responders)) {
                $UTypeArr->push($Type[2]);
            }

            if (in_array($uid, $Supervisors)) {
                if (!in_array($uid, array_merge($Responders, $Admins))) {
                    $UTypeArr->push($Type[3]);
                }
            }

            $Data->push($AddDate([
                'ref_id' => $IL->id,
                'ref_type' => "Disposition",
                'forward_to' => $uid,
                'user_type' => implode(",", $UTypeArr->toArray()),
            ]));
        }

        if (!Inbox::insert($Data->toArray())) {
            IncomingLetter::where('id', $IL->id)->forceDelete();
            return false;
        }

        if (!Comment::insert(
            $Data->map(function ($i) use ($AddDate) {
                $valid = false;
                foreach (explode(",", $i['user_type']) ?? [] as $v) {
                    if (in_array($v, ["Decision", "Responder"])) {
                        $valid = true;
                    }
                }
                if ($valid) {
                    return $AddDate([
                        'ref_id' => $i['ref_id'],
                        'ref_type' => "Disposition",
                        'created_by' => $i['forward_to'],
                    ]);
                }
                return null;
            })->filter(function ($v) {
                return !is_null($v);
            })->toArray()
        )) {
            return false;
        }

        return true;
    }

    public function EditLetter(Request $r)
    {
        // Helpers::ConvertDatelineBodyToDate($r);

        $IL = IncomingLetter::find($r->id);
        if (!is_object($IL) || in_array($IL->status, [1, 2])) {
            return false;
        }

        $File = File::whereIn('id', $r->file);
        $Files = $File->get();
        if (!is_object($Files)) {
            return false;
        }

        $C = Category::updateOrcreate([
            'name' => trim($r->cat_name),
        ]);

        $Tags = collect([]);
        if (count($r->tags ?? []) > 0) {
            foreach ($r->tags as $t) {
                if (strlen($t) != 0) {
                    $Tags->push(Tag::updateOrcreate([
                        "name" => trim($t),
                    ])->id);
                }
            }
        }

        $Data = array_merge(
            $r->all([
                'title',
                'from',
                'desc',
                // 'dateline',
                'note',
                'private',
            ]), [
                'cat_id' => $C->id ?? 0,
                'tags' => implode(",", $Tags->toArray()),
            ]
        );
        $IL->update($Data);

        $FileTrash = File::withTrashed()->where([
            'ref_id' => $r->id,
            'ref_type' => 'Disposition',
        ]);
        $FileTrashIL = FileIncomingLetter::withTrashed()->where([
            'incoming_letter_id' => $r->id,
        ]);

        foreach ($r->file as $id) {
            $q = [
                'id' => $id,
                'ref_id' => $r->id,
                'ref_type' => 'Disposition',
            ];
            if (is_object(File::onlyTrashed()->where($q)->first())) {
                File::where($q)->restore();
            }
            $q = [
                'incoming_letter_id' => $r->id,
                'file_id' => $id,
            ];
            if (is_object(FileIncomingLetter::onlyTrashed()->where($q)->first())) {
                FileIncomingLetter::where($q)->restore();
            } else if (!is_object(FileIncomingLetter::withTrashed()->where($q)->first())) {
                FileIncomingLetter::create($q);
            }
        }
        $FileTrash->whereNotIn('id', $r->file)->delete();
        $FileTrashIL->whereNotIn('file_id', $r->file)->delete();

        $File->update([
            'ref_type' => "Disposition",
            'ref_id' => $IL->id,
            'is_used' => 1,
        ]);

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_type' => "Disposition",
            'ref_id' => $r->id,
            'action' => "Edit",
            'message_id' => "Merubah surat disposisi",
            'message_en' => "Editing a disposition letter",
        ]);

        $Query = [
            'ref_id' => $IL->id,
            'ref_type' => "Disposition",
        ];

        $SPVs = [];
        if (!$r->private) {
            $PIDs = Permission::whereIn("name", ["SIAP.Disposition.Level.A", "SIAP.Disposition.Level.B"])->get()->map(function ($i) {
                return $i['id'];
            })->toArray();
            $UPs = UserPermission::whereIn("permission_id", $PIDs)->where("is_active", 1);
            $SPVs = $UPs->get()->map(function ($i) {
                return $i['user_id'];
            })->toArray();
        }

        $Responders = $r->user_responders ?? [];
        $Supervisors = array_unique(array_merge($r->user_supervisors ?? [], $SPVs ?? []));
        $Admins = array_unique(array_merge($Admins ?? [], [$r->UserData->id]));

        $AllUser = array_unique(array_merge($Responders, $Supervisors, [$r->user_decision]));
        $OldUIDs = Inbox::where($Query)->get()->map(function ($i) {
            return $i['forward_to'];
        })->toArray();

        $DeleteUser = collect([]);
        foreach (array_unique($OldUIDs) as $olduid) {
            if (!in_array($olduid, array_unique(array_merge($AllUser, [$r->UserData->id])))) {
                $DeleteUser->push($olduid);
            }
        }
        $DeleteUser = $DeleteUser->toArray();

        $CreateComment = function ($uid) use ($Query) {
            (new CommentRepository())->CreateComment($Query, $uid);
        };

        $CheckRD = function ($uid) use ($Responders, $r) {
            return in_array($uid, array_unique(array_merge($Responders, [$r->user_decision])));
        };

        foreach ($AllUser as $uid) {
            $Type = ["Decision", "Responder", "Supervisor"];
            $UTypeArr = collect([]);
            if ($uid == $r->user_decision) {
                $UTypeArr->push($Type[0]);
            }

            if (in_array($uid, $Responders)) {
                $UTypeArr->push($Type[1]);
            }

            if (in_array($uid, $Supervisors) && !$CheckRD($uid)) {
                $UTypeArr->push($Type[2]);
            }

            $I = Inbox::onlyTrashed()->where(array_merge($Query, [
                'forward_to' => $uid,
            ]));

            // Check Has Already Inbox
            if (is_object($I->first())) {
                if ($CheckRD($uid)) {
                    $CreateComment($uid);
                }
                $I->update([
                    'user_type' => implode(",", $UTypeArr->toArray()),
                ]);
                $I->restore();
            } else {
                if ($CheckRD($uid)) {
                    $CreateComment($uid);
                }
                $Check = Inbox::where(array_merge($Query, [
                    'forward_to' => $uid,
                ]));
                if (is_null($Check->first())) {
                    Inbox::create(array_merge($Query, [
                        'forward_to' => $uid,
                        'user_type' => implode(",", $UTypeArr->toArray()),
                    ]));
                } else {
                    $Check->update(array_merge($Query, [
                        'forward_to' => $uid,
                        'user_type' => implode(",", $UTypeArr->toArray()),
                    ]));
                }
            }
        }

        if (count($DeleteUser) > 0) {
            Inbox::where($Query)->whereIn('forward_to', $DeleteUser)->delete();
            Comment::where($Query)->whereIn('created_by', $DeleteUser)->delete();
        }

        return true;
    }

    public function CommentLetter(Request $r)
    {
        $C = Comment::find($r->comment_id);
        if (!is_object($C)) {
            return false;
        }

        $C->update($r->all(['comment']));

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_id' => $C->ref_id,
            'ref_type' => "Disposition",
            'action' => "EditComment",
            'message_id' => "Mengubah komentar surat disposisi",
            'message_en' => "Change the disposition letter comments",
        ]);

        return true;
    }

    public function AddResponderLetter(Request $r)
    {
        $D = IncomingLetter::find($r->disposition_id);
        if (!is_object($D) || in_array($D->status, [1, 2])) {
            return false;
        }
        $Query = [
            'ref_id' => $D->id,
            'ref_type' => "Disposition",
        ];

        $Check = Inbox::where($Query)->whereIn('forward_to', $r->input('to', []));
        $Check = $Check->get();

        $HasBeenAdded = $Check->map(function ($i) {
            return $i['forward_to'];
        })->toArray();

        $CreateComment = function ($uid) use ($Query) {
            (new CommentRepository())->CreateComment($Query, $uid);
        };

        $HasChange = false;
        foreach ($r->input('to', []) as $k) {
            if (!in_array($k, $HasBeenAdded)) {
                Inbox::create(array_merge($Query, [
                    'forward_to' => $k,
                    'user_type' => "Responder",
                ]));
                $CreateComment($k);
                $HasChange = true;
            }
        }

        $Check = $Check->toArray();
        if (count($Check) > 0) {
            foreach ($Check as $k => $i) {
                $I = Inbox::withTrashed()->where(array_merge($Query, [
                    'forward_to' => $i['forward_to'],
                ]));
                $UTypes = explode(",", $i['user_type']) ?? [];
                if (!in_array("Responder", $UTypes)) {
                    $I->update([
                        'user_type' => implode(",", array_merge($UTypes, ["Responder"])),
                    ]);
                    $I->restore();
                    $CreateComment($i['forward_to']);
                    $HasChange = true;
                }
            }
        }

        if ($HasChange) {
            $RoleName = $r->UserData->role->role->name ?? "";
            (new ExtensionRepository())->AddActivity([
                'user_id' => $r->UserData->id,
                'ref_type' => "Disposition",
                'ref_id' => $r->disposition_id,
                'action' => "AddResponderDisposition",
                'message_id' => "{$RoleName} menambah penanggap di surat disposisi",
                'message_en' => "{$RoleName} adding a responder in disposition letter",
            ]);
        }

        return true;
    }

    public function SendLetter(Request $r)
    {
        $D = IncomingLetter::find($r->disposition_id);
        if (!is_object($D) || in_array($D->status, [1, 2])) {
            return false;
        }
        $Query = [
            'ref_id' => $D->id,
            'ref_type' => "Disposition",
        ];

        $Check = Inbox::where(array_merge($Query, [
            'forward_to' => $r->to,
        ]));
        $I = $Check->first();
        if (is_null($I)) {
            Inbox::create(array_merge($Query, [
                'forward_to' => $r->to,
                'user_type' => "Receiver",
            ]));
        } else {
            $Check->update(array_merge($Query, [
                'forward_to' => $r->to,
                'user_type' => implode(",", array_merge(explode(",", $I->user_type) ?? [], ["Receiver"])),
            ]));
        }

        $D->update([
            'status' => 1,
        ]);

        $Name = User::select("id", "name")->where('id', $r->to)->first()->name ?? "";
        $RoleID = UserPermission::select("role_id")->where(["user_id" => $r->to, "is_active" => 1])->first()->role_id ?? "";
        $RoleName = Role::where('id', $RoleID)->first()->name ?? "";
        $Title = $D->title ?? "";
        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_type' => "Disposition",
            'ref_id' => $r->disposition_id,
            'action' => "SendDisposition",
            'message_id' => "Mengirim surat disposisi {$Title} ke {$RoleName} dengan nama {$Name}",
            'message_en' => "Send a disposition letter {$Title} to {$RoleName} under the name {$Name}",
        ]);
        // Todo: Kirim Surat Perintah Ke User Yg Diberikan Tugas
        return true;
    }
}
