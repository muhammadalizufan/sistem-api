<?php
namespace App\Repositories\SIAP;

use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\Extension\File;
use App\Models\SIAP\Comment;
use App\Models\SIAP\Inbox;
use App\Models\SIAP\RequestData;
use App\Repositories\Extension\ExtensionRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait RequestDataRepository
{
    public function AddNewRequestData(Request $r)
    {
        $RD = RequestData::create(array_merge(
            $r->all([
                'requester',
                'agency',
                'phone',
                'desc',
            ]), [
                'cat_id' => 0,
                'user_id' => $r->UserData->id,
                'code' => 'SIAP/PD/' . time(),
                'file_original_id' => $r->input('file_id', 0),
                'email' => '',
                'status' => 0,
                'is_archive' => 0,
            ])
        );
        if (!is_object($RD)) {
            return false;
        }

        $F = File::find($r->input('file_id', 0));
        $F->update([
            'ref_type' => "RequestData",
            'ref_id' => $RD->id,
            'is_used' => 1,
        ]);

        $AddDate = function (array $array = []) {
            return array_merge($array, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        };

        $UAdmin = UserPermission::where(
            "permission_id",
            Permission::where("name", "SIAP.RequestData.Level.Z")->first()->id ?? 0
        )->where("is_active", 1)->get()->map(function ($i) {
            return $i['user_id'];
        })->toArray();

        $PIDs = Permission::whereIn("name", ["SIAP.RequestData.Level.A", "SIAP.RequestData.Level.B"])->get()->map(function ($i) {
            return $i['id'];
        })->toArray();
        $UPs = UserPermission::whereIn("permission_id", $PIDs)->where("is_active", 1);
        $SPVs = $UPs->get()->map(function ($i) {
            return $i['user_id'];
        })->toArray();

        $Confirmers = array_unique($r->user_confirmers ?? []);
        $Supervisors = array_unique($SPVs ?? []);
        $Admins = array_unique($UAdmin ?? []);

        $Data = collect([]);

        foreach (array_unique(array_merge($Confirmers, $Supervisors, $Admins, [$r->UserData->id])) as $uid) {
            $Type = ["Administator", "Creator", "Confirmer", "Supervisor"];
            $UTypeArr = collect([]);

            if (in_array($uid, $Admins)) {
                $UTypeArr->push($Type[0]);
            }

            if ($uid == $r->UserData->id) {
                $UTypeArr->push($Type[1]);
            }

            if (in_array($uid, $Confirmers)) {
                $UTypeArr->push($Type[2]);
            }

            if (in_array($uid, $Supervisors) && !in_array($uid, array_merge($Confirmers, $Admins))) {
                $UTypeArr->push($Type[3]);
            }

            $Data->push($AddDate([
                'ref_id' => $RD->id,
                'ref_type' => "RequestData",
                'forward_to' => $uid,
                'user_type' => implode(",", $UTypeArr->toArray()),
            ]));
        }

        if (!Inbox::insert($Data->toArray())) {
            RequestData::where('id', $RD->id)->forceDelete();
            return false;
        }

        if (!Comment::insert(
            $Data->map(function ($i) use ($AddDate) {
                if (in_array("Confirmer", explode(",", $i['user_type']) ?? [""])) {
                    return $AddDate([
                        'ref_id' => $i['ref_id'],
                        'ref_type' => "RequestData",
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

    public function EditRequestData(Request $r)
    {
        $RD = RequestData::find($r->request_data_id);
        if (!is_object($RD) || in_array($RD->status, [1, 2])) {
            return false;
        }

        $F = File::find($r->file_id);
        if (!is_object($F)) {
            return false;
        }
        $FOld = $RD->file_original_id;

        $Data = array_merge(
            $r->all([
                'requester',
                'agency',
                'phone',
                'desc',
            ]), [
                'file_original_id' => $r->input('file_id', 0),
            ]
        );
        $RD->update($Data);

        if ($FOld != $r->file_id) {
            File::where('id', $FOld)->delete();
            $F->update([
                'ref_type' => "RequestData",
                'ref_id' => $RD->id,
                'is_used' => 1,
            ]);
        }

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_type' => "RequestData",
            'ref_id' => $r->id,
            'action' => "Edit",
            'message_id' => "Merubah surat permintaan data",
            'message_en' => "Editing a RequestData letter",
        ]);

        return true;
    }

    public function AddConfirmerRequestData(Request $r)
    {
        $RD = RequestData::find($r->request_data_id);
        if (!is_object($RD) || in_array($RD->status, [1, 2])) {
            return false;
        }

        $Query = [
            'ref_id' => $RD->id,
            'ref_type' => "RequestData",
        ];

        $OldUIDs = Inbox::where(array_merge($Query, [
            "user_type" => "Confirmer",
        ]))->get()->map(function ($i) {
            return $i['forward_to'];
        })->toArray();

        $DeleteUser = collect([]);
        foreach (array_unique($OldUIDs) as $olduid) {
            if (!in_array($olduid, $r->to ?? [])) {
                $DeleteUser->push($olduid);
            }
        }
        $DeleteUser = $DeleteUser->toArray();

        $PIDs = Permission::whereIn("name", ["SIAP.RequestData.Level.A", "SIAP.RequestData.Level.B"])->get()->map(function ($i) {
            return $i['id'];
        })->toArray();
        $UPs = UserPermission::whereIn("permission_id", $PIDs)->where("is_active", 1);
        $SPVs = $UPs->get()->map(function ($i) {
            return $i['user_id'];
        })->toArray();

        $CreateComment = function ($uid) use ($Query) {
            (new CommentRepository())->CreateComment($Query, $uid);
        };
        $Confirmers = array_unique($r->to ?? []);
        $Supervisors = array_unique($SPVs ?? []);
        foreach (\array_merge($Confirmers, $Supervisors) as $uid) {
            $Type = ["Confirmer", "Supervisor"];
            $UTypeArr = collect([]);

            if (in_array($uid, $Confirmers)) {
                $UTypeArr->push($Type[0]);
            }

            if (in_array($uid, $Supervisors)) {
                $UTypeArr->push($Type[1]);
            }

            $I = Inbox::onlyTrashed()->where(array_merge($Query, [
                'forward_to' => $uid,
            ]));

            if (is_object($I->first())) {
                if (in_array($uid, $Confirmers)) {
                    $CreateComment($uid);
                }
                $I->update([
                    'user_type' => "Confirmer",
                ]);
                $I->restore();
            } else {
                if (in_array($uid, $Confirmers)) {
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

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_id' => $RD->id,
            'ref_type' => "RequestData",
            'action' => "AddConfirmer",
            'message_id' => "Menambahkan penanggap pada surat permintaan data",
            'message_en' => "Add a responder at request data letter",
        ]);

        return true;
    }

    public function CommentRequestData(Request $r)
    {
        $C = Comment::find($r->input('comment_id', 0));
        if (!is_object($C)) {
            return false;
        }

        $C->update($r->all(['comment']));

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_id' => $C->ref_id,
            'ref_type' => "RequestData",
            'action' => "EditComment",
            'message_id' => "Mengubah komentar surat permintaan data",
            'message_en' => "Change the request data letter comment",
        ]);

        return true;
    }

    public function ConfirmationRequestData(Request $r)
    {
        $RD = RequestData::find($r->request_data_id);
        if (!is_object($RD)) {
            return false;
        }

        $F = File::find($r->file_id);
        $FOld = $RD->file_edited_id;
        if (!is_object($F)) {
            return false;
        }

        $status = 0;
        $translateMsg = "";
        switch ($r->input('status', 'Reject')) {
            case 'Approve':
                $status = 1;
                $translateMsg = "diterima";
                break;
            case 'Reject':
                $status = 2;
                $translateMsg = "ditolak";
                break;
        }

        $RD->update([
            'file_edited_id' => $r->file_id,
            'status' => $status,
        ]);

        if ($FOld != $r->file_id) {
            File::where('id', $FOld)->delete();
            $F->update([
                'ref_id' => $RD->id,
                'ref_type' => "RequestData",
                'is_used' => 1,
            ]);
        }

        $CreateActivity = function (string $messageId = "", string $messageEn = "") use ($r, $RD) {
            (new ExtensionRepository())->AddActivity([
                'user_id' => $r->input('user_id', 0),
                'ref_type' => "RequestData",
                'ref_id' => $RD->id,
                'action' => "EditRequestData",
                'message_id' => $messageId,
                'message_en' => $messageEn,
            ]);
        };

        if ($RD->status != $status) {
            $translateMsgEn = \strtolower($r->input('status', ''));
            $CreateActivity("Merubah status surat permintaan data menjadi {$translateMsg}", "Change the request data letter status to {$translateMsgEn}");
        }

        return true;
    }
}
