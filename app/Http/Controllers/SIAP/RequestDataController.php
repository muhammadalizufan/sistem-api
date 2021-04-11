<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\SIAP\ForwardRequestData;
use App\Repositories\SIAP\SIAPRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class RequestDataController extends Controller
{
    private $SIAPRepo;

    public function __construct()
    {
        $this->SIAPRepo = new SIAPRepository;
    }

    public function GetRequestDataResponders(Request $r)
    {
        $UP = UserPermission::with("Role");
        $PID = Permission::where("name", "SIAP.RequestData.Responders")->first()->id ?? 0;
        $UP = $UP->where("permission_id", $PID)->select("role_id")->distinct("role_id")->get();

        return $UP->map(function ($i) {
            return [
                "id" => $i['role_id'],
                "name" => $i['role']['name'],
            ];
        });
    }

    public function GetRequestDataInbox(Request $r, ?int $id = null)
    {
        $FRD = ForwardRequestData::with([
            'RequestData' => function ($q) {
                $q->with(['Responders', 'FileOriginal', 'FileEdited']);
            },
            'User',
        ]);
        $FRD = $FRD->whereHas("RequestData", function ($q) use ($r) {
            if ($r->has("status") && !empty($r->input("status"))) {
                $q->whereIn("status", $r->input("status", [0]));
            }
            $q->where("is_archive", $r->input("archive", 0));
        });

        // Remove Receive Letter
        if (is_object($r->UserData)) {
            $UID = $r->UserData->id;
            $FRD = $FRD->where("user_id", $UID);
        }

        if (is_null($id)) {
            return $FRD->paginate($r->input('limit', 10));
        } else {
            return response($FRD->where('id', $id)->first(), 200);
        }
    }

    private static function AddRequestDataRule(): array
    {
        return [
            'requested_data' => 'required|string',
            'requester' => 'required|string',
            'agency' => 'required|string',
            'phone' => 'required|string',
            'file' => 'required|string',
            'file_id' => 'required|integer|min:1',
        ];
    }

    public function AddRequestDataHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddRequestDataRule());
            $r->request->add(['user_id' => $r->UserData->id]);
            $AL = $this->SIAPRepo->AddNewRequestData($r);
            if (!$AL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddLetterDispositionException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add new request data",
        ], 201);
    }

    public function AddConfirmerRequestDataHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'confirmers' => 'required|min:1|array',
                'confirmers.*' => 'required',
            ]);
            $r->request->add(['request_data_id' => $id]);
            $AL = $this->SIAPRepo->AddConfirmerRequestData($r);
            if (!$AL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddLetterDispositionException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add confirmer a request data",
        ], 200);
    }

    public function ConfirmationRequestDataHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'file' => 'required|string',
                'file_id' => 'required|integer|min:1',
                'status' => 'required|string|in:Process,Approve,Reject',
            ]);
            $r->request->add(['request_data_id' => $id]);
            $AL = $this->SIAPRepo->ConfirmationRequestData($r);
            if (!$AL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddLetterDispositionException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success confirmation a requested data",
        ], 200);
    }

    public function CommentRequestDataHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'comment' => 'required|string',
            ]);
            $r->request->add(['forward_request_data_id' => $id]);
            $AL = $this->SIAPRepo->CommentForwardRequestData($r);
            if (!$AL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddLetterDispositionException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success comment on requested data",
        ], 200);
    }
}
