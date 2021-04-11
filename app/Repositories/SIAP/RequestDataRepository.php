<?php
namespace App\Repositories\SIAP;

use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\Extension\File;
use App\Models\SIAP\ForwardRequestData;
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
                'user_id',
                'requested_data',
                'requester',
                'agency',
                'phone',
                'requester',
                'requested_data',
            ]), [
                'cat_id' => 0,
                'code' => 'SIAP/PD/' . time(),
                'file_original' => $r->input('file'),
                'email' => '',
                'status' => 0,
                'is_archive' => 0,
            ])
        );
        if (!is_object($RD)) {
            return [
                'status' => false,
                'message' => 'failed create a new request data',
            ];
        }

        // Update reference id on files table
        $F = File::find($r->input('file_id', 0));
        if (is_object($F)) {
            $F->update([
                'ref_id' => $RD->id,
            ]);
        }

        // Send forward request data to administrator and requester
        $PAID = Permission::where(["name" => "SIAP.RequestData.Administrator", "is_active" => 1])->select('id')->first()->id ?? 0;
        $FRD = ForwardRequestData::insert([
            0 => [
                'request_data_id' => $RD->id ?? 0,
                'user_id' => UserPermission::where("permission_id", $PAID)->select('user_id')->first()->user_id ?? 0,
                'types' => 0, // Administrator
                'comment' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            1 => [
                'request_data_id' => $RD->id ?? 0,
                'user_id' => $r->user_id ?? 0,
                'types' => 1, // Requester
                'comment' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);

        if (!$FRD) {
            RequestData::where('id', $RD->id)->forceDelete();
            return [
                'status' => false,
                'message' => 'failed create a new request data',
            ];
        }

        return [
            'status' => true,
        ];
    }

    public function AddConfirmerRequestData(Request $r)
    {
        // Search Role is has a permission responder
        $PRID = Permission::where(["name" => "SIAP.RequestData.Responders", "is_active" => 1])->select('id')->first()->id ?? 0;
        $ForwardList = UserPermission::whereIn("role_id", $r->input('confirmers', []))->where("permission_id", $PRID)->get()
            ->map(function ($i) use ($r) {
                return [
                    'request_data_id' => $r->request_data_id ?? 0,
                    'user_id' => $i['user_id'],
                    'types' => 2,
                    'comment' => null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            })
            ->toArray();
        if (count($ForwardList) <= 0) {
            return [
                'status' => false,
                'message' => 'failed create a new request data',
            ];
        }

        $FRD = ForwardRequestData::insert($ForwardList);
        if (!$FRD) {
            return [
                'status' => false,
                'message' => 'failed create a new request data',
            ];
        }

        return [
            'status' => true,
        ];
    }

    public function CommentForwardRequestData(Request $r)
    {
        $FRD = ForwardRequestData::find($r->input('forward_request_data_id', 0));
        if (!is_object($FRD)) {
            return [
                'status' => false,
                'message' => 'failed comment on requested data',
            ];
        }

        $FRD->update($r->all([
            'comment',
        ]));

        return [
            'status' => true,
        ];
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
            'file_edited' => $r->input('file'),
            'status' => $status,
        ]);

        // Update reference id on files table
        $F = File::find($r->input('file_id', 0));
        if (is_object($F)) {
            $F->update([
                'ref_id' => $RD->id,
            ]);
        }

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
