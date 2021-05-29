<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\SIAP\Comment;
use App\Models\SIAP\Inbox;
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

    public function GetRequestDataConfirmers()
    {
        $UP = UserPermission::with("Role");
        $PID = Permission::where("name", "SIAP.RequestData.Level.A2")->first()->id ?? 0;
        $UP = $UP->where("permission_id", $PID)->select("role_id", "user_id")->get();

        return $UP->map(function ($i) {
            return [
                "id" => $i['user_id'],
                "name" => $i['role']['name'],
            ];
        });
    }

    public function GetRequestDataInbox(Request $r, ?int $id = null)
    {
        $I = Inbox::with(['RequestData', 'User']);
        $I = $I->whereHas("RequestData", function ($q) use ($id, $r) {
            if (is_null($id)) {
                $q->whereIn("status", $r->input("status", [0]));
                $q->where("is_archive", $r->input("archive", 0));
            }
        });
        $I = $I->where("ref_type", "RequestData")->where("forward_to", $r->UserData->id);

        if (is_null($id)) {
            $Payload = collect($I->paginate($r->input('limit', 10)))->toArray();
            $Payload['data'] = collect($Payload['data'])->map(function ($i) {
                $i['user'] = [
                    'id' => $i['user']['id'],
                    'name' => $i['user']['name'],
                    'role_id' => $i['user']['role']['role']['id'],
                    'role_name' => $i['user']['role']['role']['name'],
                ];
                return $i;
            });
            return $Payload;
        }
        $Payload = $I->where('id', $id)->first();
        if (is_object($Payload)) {
            $Payload = $Payload->toArray();
            $Payload['user'] = [
                'id' => $Payload['user']['id'],
                'name' => $Payload['user']['name'],
                'role_id' => $Payload['user']['role']['role']['id'],
                'role_name' => $Payload['user']['role']['role']['name'],
            ];

            // Confirmers
            $Payload['confirmers'] = Comment::with(["User", "RequestData"])->where([
                "ref_id" => $Payload['ref_id'],
                "ref_type" => "RequestData",
            ])->whereHas("RequestData", function ($q) {
                $q->whereRaw("FIND_IN_SET('Confirmer', `inboxs`.`user_type`) != 0");
            })->where("created_by", "!=", $FID ?? 0)->get()->map(function ($i) {
                return [
                    'id' => $i['id'],
                    'user_id' => $i['user']['id'],
                    'role_id' => $i['user']['role']['role_id'] ?? null,
                    'user_name' => $i['user']['name'] ?? null,
                    'role_name' => $i['user']['role']['role']['name'] ?? null,
                    'updated_at' => $i['updated_at'],
                    'comment' => $i['comment'],
                ];
            });
        }

        return response($Payload, 200);
    }

    private static function AddRequestDataRule(): array
    {
        return [
            'desc' => 'required|string',
            'requester' => 'required|string',
            'agency' => 'required|string',
            'phone' => 'required|string',
            'file_id' => 'required|integer|min:1',
        ];
    }

    private static function EditRequestDataRule(Request $r): array
    {
        // $PID = Permission::where('name', "SIAP.RequestData.Level.Z")->first()->id ?? 0;
        // $Count = UserPermission::selectRaw("COUNT(`user_id`) as count")->where([
        //     'user_id' => $r->UserData->id ?? 0,
        //     'permission_id' => $PID,
        // ])->first()->count ?? 0;
        // $IsAdmin = $Count > 0 ? true : false;

        $Rule = [
            'desc' => 'required|string',
            'requester' => 'required|string',
            'agency' => 'required|string',
            'phone' => 'required|string',
            'file_id' => 'required|integer|min:1',
        ];

        // TODO : Menambah rule ketika admin yg update
        // if ($IsAdmin) {
        //     $Rule = array_merge($Rule);
        // }
        return $Rule;
    }

    public function AddRequestDataHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddRequestDataRule());
            if (!$this->SIAPRepo->AddNewRequestData($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed add new request data", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add new request data",
        ], 201);
    }

    public function EditRequestDataHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::EditRequestDataRule($r));
            $r->request->add(['request_data_id' => $id]);
            if (!$this->SIAPRepo->EditRequestData($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed edit a request data", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success edit a request data",
        ], 201);
    }

    public function AddConfirmerRequestDataHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                "request_data_id" => "required|integer|min:1",
                "to" => "required|array|min:1",
                "to.*" => "required",
            ]);
            if (!$this->SIAPRepo->AddConfirmerRequestData($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed add confirmer a request data", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add confirmer a request data",
        ], 200);
    }

    public function CommentRequestDataHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'comment_id' => 'required|integer|min:1',
                'comment' => 'required|string',
            ]);
            if (!$this->SIAPRepo->CommentRequestData($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed comment a request data", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success comment a request data",
        ], 200);
    }

    public function ConfirmationRequestDataHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'request_data_id' => 'required|integer|min:1',
                'file_id' => 'required|integer|min:1',
                'status' => 'required|string|in:Approve,Reject',
            ]);
            if (!$this->SIAPRepo->ConfirmationRequestData($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed confirmation a request data", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success confirmation a requested data",
        ], 200);
    }

}
