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
                'requested_data',
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

        $Data = collect([]);

        foreach (array_unique(array_merge($r->user_confirmers ?? [], $SPVs ?? [], $UAdmin ?? [], [$r->UserData->id])) as $uid) {
            $Type = ["Administator", "Confirmer", "Supervisor"];
            $UTypeArr = collect([]);

            if (in_array($uid, array_merge([$r->UserData->id], $UAdmin ?? []))) {
                $UTypeArr->push($Type[0]);
            }

            if (in_array($uid, $r->user_confirmers ?? [])) {
                $UTypeArr->push($Type[1]);
            }

            if (in_array($uid, array_merge($SPVs ?? [])) && !in_array($uid, array_merge($r->user_confirmers ?? [], $UAdmin ?? [], [$r->UserData->id]))) {
                $UTypeArr->push($Type[2]);
            }

            $Data->push(
                $AddDate([
                    'ref_id' => $RD->id,
                    'ref_type' => "RequestData",
                    'forward_to' => $uid,
                    'user_type' => implode(",", $UTypeArr->toArray()),
                ])
            );
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
        $FOld = $RD->file_id;
        $Data = array_merge(
            $r->all([
                'requested_data',
                'requester',
                'agency',
                'phone',
                'desc',
            ])
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
            'message_id' => "Merubah surat disposisi",
            'message_en' => "Editing a RequestData letter",
        ]);

        $Query = [
            'ref_id' => $RD->id,
            'ref_type' => "RequestData",
        ];

        $PIDs = Permission::whereIn("name", ["SIAP.RequestData.Level.A", "SIAP.RequestData.Level.B"])->get()->map(function ($i) {
            return $i['id'];
        })->toArray();
        $UPs = UserPermission::whereIn("permission_id", $PIDs)->where("is_active", 1);
        $SPVs = $UPs->get()->map(function ($i) {
            return $i['user_id'];
        })->toArray();

        $AllUser = array_unique(array_merge($r->user_confirmers ?? [], $SPVs ?? []));
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
            $C = Comment::withTrashed()->where(array_merge($Query, [
                'created_by' => $uid,
            ]));
            if (is_null($C->first())) {
                Comment::create(array_merge($Query, [
                    'created_by' => $uid,
                    'comment' => null,
                ]));
            } else {
                if (is_object(
                    Comment::onlyTrashed()->where(array_merge($Query, [
                        'created_by' => $uid,
                    ]))->first()
                )) {
                    $C->update([
                        'comment' => null,
                    ]);
                    $C->restore();
                }
            }
        };

        foreach ($AllUser as $uid) {
            $Type = ["Confirmer", "Supervisor"];
            $UTypeArr = collect([]);
            if (in_array($uid, $r->user_confirmers ?? [])) {
                $UTypeArr->push($Type[1]);
            }
            if (in_array($uid, array_unique(array_merge($r->user_confirmers ?? [], $SPVs ?? [])))) {
                if (!in_array($uid, array_merge($r->user_confirmers ?? []))) {
                    $UTypeArr->push($Type[2]);
                }
            }

            $I = Inbox::onlyTrashed()->where(array_merge($Query, [
                'forward_to' => $uid,
            ]));

            if (is_object($I->first())) {
                if (!in_array($uid, array_unique(array_merge($SPVs ?? [])))) {
                    if (in_array($uid, array_merge($r->user_confirmers ?? []))) {
                        $CreateComment($uid);
                    }
                }
                $I->update([
                    'user_type' => implode(",", $UTypeArr->toArray()),
                ]);
                $I->restore();
            } else {
                if (!in_array($uid, array_unique(array_merge($SPVs ?? [])))) {
                    if (in_array($uid, array_merge($r->user_confirmers ?? []))) {
                        $CreateComment($uid);
                    }
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

    public function CommentForwardRequestData(Request $r)
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
        $RD = RequestData::find($r->input('request_data_id', 0));
        if (!is_object($RD)) {
            return [
                'status' => false,
                'message' => 'failed confirmation on requested data',
            ];
        }

        $status = 0;
        $translateMsg = "";
        switch ($r->input('status', 'Process')) {
            case 'Approve':
                $status = 1;
                $translateMsg = "diterima";
                break;
            case 'Reject':
                $status = 2;
                $translateMsg = "ditolak";
                break;
            case 'Process':
                $status = 0;
                $translateMsg = "proses";
                break;
        }

        $RD->update([
            'file_edited_id' => $r->input('file_id'),
            'status' => $status,
        ]);

        // Update reference id on files table
        $F = File::find($r->input('file_id', 0));
        $F->update([
            'ref_type' => "RequestData",
            'ref_id' => $RD->id,
            'is_used' => 1,
        ]);

        $CreateActivity = function (string $messageId = "", string $messageEn = "") use ($r, $RD) {
            (new ExtensionRepository())->AddActivity([
                'user_id' => $r->input('user_id', 0),
                'ref_type' => 2, // Outgoing Letter
                'ref_id' => $RD->id,
                'action' => "EditOutgoingLetter",
                'message_id' => $messageId,
                'message_en' => $messageEn,
            ]);
        };

        // if old status letter edited and cannot same status
        if ($RD->status != $status) {
            $translateMsgEn = \strtolower($r->input('status', ''));
            $CreateActivity("Merubah status surat keluar menjadi {$translateMsg}", "Change the outgoing mail status to {$translateMsgEn}");
        }

        return [
            'status' => true,
        ];
    }
}
