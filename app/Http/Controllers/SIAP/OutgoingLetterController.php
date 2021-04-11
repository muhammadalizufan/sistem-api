<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\Extension\Activity;
use App\Models\SIAP\OutgoingLetter;
use App\Repositories\SIAP\SIAPRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class OutgoingLetterController extends Controller
{
    private $SIAPRepo;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->SIAPRepo = new SIAPRepository;
    }

    public function GetOutgoingLetterActivityHandler(Request $r, ?int $id = null)
    {
        $A = Activity::with(["User"]);
        $OLID = $r->input('olid', 0);
        $A = $A->where(array_merge(['ref_type' => "OutgoingLetter"], ($OLID != 0 ? ['ref_id' => $OLID] : [])));
        if (is_null($id)) {
            $A = collect($A->get()->toArray())->map(function ($i) {
                $i['user']['role'] = $i['user']['role']['role'] ?? null;
                return $i;
            });
        } else {
            $A = $A->where('id', $id)->first()->toArray();
            $A['user']['role'] = $A['user']['role']['role'] ?? null;
        }
        return response($A, 200);
    }

    public function GetOutgoingLetterHandler(Request $r, ?int $id = null)
    {
        $limit = 10;
        if (\is_int($r->input('limit'))) {
            $limit = $r->input('limit', 10);
        }

        $OL = OutgoingLetter::with("User", "Category");
        if ($r->has("status") && !empty($r->input("status"))) {
            $OL = $OL->where("status", $r->input("status", 0));
        }
        $OL = $OL->where("is_archive", $r->input("is_archive", 0));

        $UID = $r->UserData->id ?? 0;
        if ($UID != 0) {
            if (is_null(UserPermission::where([
                'user_id' => $UID,
                'permission_id' => Permission::where('name', "SIAP.OutgoingLetter.Approver")->first()->id ?? 0,
            ])->first())) {
                $OL = $OL->where('user_id', $r->UserData->id);
            }
        }

        if (is_null($id)) {
            $OL = collect($OL->paginate($limit))->toArray();
            $OL['data'] = collect($OL['data'])->map(function ($i) {
                $i['user'] = [
                    "id" => $i['user']['id'],
                    "role_id" => $i['user']['role']['role_id'],
                    "name" => $i['user']['name'],
                    "role_name" => $i['user']['role']['role']['name'],
                ];
                return $i;
            });
        } else {
            $OL = $OL->where('id', $id)->first();
            if (is_object($OL)) {
                $OL = $OL->toArray();
                $OL['user'] = [
                    "id" => $OL['user']['id'],
                    "role_id" => $OL['user']['role']['role_id'],
                    "name" => $OL['user']['name'],
                    "role_name" => $OL['user']['role']['role']['name'],
                ];
            }
            return \response($OL, 200);
        }
        return $OL;
    }

    private static function AddNewLetterRule(): array
    {
        return [
            'title' => 'required|string',
            'to' => 'required|string',
            'agency' => 'required|string',
            'address' => 'required|string',
            'cat_name' => 'required|string',
            'original_letter' => 'required|string',
        ];
    }

    private static function EditLetterRule(Request $r): array
    {
        $PID = Permission::where(["name" => "SIAP.OutgoingLetter.Approver", "is_active" => 1])->first()->id ?? 0;
        $UP = UserPermission::where(["user_id" => $r->UserData->id, "permission_id" => $PID, "is_active" => 1])->first();
        if (is_object($UP)) {
            return [
                'note' => 'string',
                'validated_letter' => 'required|string',
                'status' => 'required|string|in:Approve,Reject',
            ];
        } else {
            return self::AddNewLetterRule();
        }
    }

    public function AddNewLetterHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddNewLetterRule());
            $r->request->add(['user_id' => $r->UserData->id]);
            if (!$this->SIAPRepo->AddNewOutgoingLetter($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed add new letter", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddLetterDispositionException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add new letter",
        ], 201);
    }

    public function EditLetterHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::EditLetterRule($r));
            $r->request->add(['id' => $id, 'user_id' => $r->UserData->id]);
            if (!$this->SIAPRepo->EditOutgoingLetter($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed edit letter", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success edit letter",
        ], 200);
    }
}
