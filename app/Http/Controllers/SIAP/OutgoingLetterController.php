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
    private $SIAPRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->SIAPRepository = new SIAPRepository;
    }

    public function GetOutgoingLetterActivityHandler(Request $r, ?int $id = null)
    {
        $A = Activity::with(["User"]);
        $OLID = $r->input('olid', 0);
        // ref_type 2 = Outgoing Letter
        $A = $A->where(\array_merge(['ref_type' => 2], ($OLID != 0 ? ['ref_id' => $OLID] : [])));
        if (is_null($id)) {
            $A = \collect($A->get()->toArray())->map(function ($i) {
                $i['user']['role'] = $i['user']['role']['role'] ?? null;
                return $i;
            });
        } else {
            $A = $A->where('id', $id)->first()->toArray();
            $A['user']['role'] = $A['user']['role']['role'] ?? null;
        }
        return \response($A ?? null, 200);
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
                $i['user']['role'] = $i['user']['role']['role'];
                return $i;
            });
        } else {
            $OL = $OL->where('id', $id)->first();
            if (is_object($OL)) {
                $OL = $OL->toArray();
                $OL['user']['role'] = $OL['user']['role']['role'];
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
            'note' => 'string|nullable',
        ];
    }

    private static function EditLetterRule(): array
    {
        return [
            'title' => 'required|string',
            'to' => 'required|string',
            'agency' => 'required|string',
            'address' => 'required|string',
            'cat_name' => 'required|string',
            'original_letter' => 'required|string',
            'validated_letter' => 'required|string',
            'note' => 'string',
            'status' => 'required|string|in:Process,Approve,Reject',
        ];
    }

    public function AddNewLetterHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddNewLetterRule());
            $r->request->add(['user_id' => $r->UserData->id]);
            $AL = $this->SIAPRepository->AddNewOutgoingLetter($r->all());
            if (!$AL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AL['message'], 400);
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
            ValidatorManager::ValidateJSON($r, self::EditLetterRule());
            $r->request->add(['outcoming_letter_id' => $id, 'user_id' => $r->UserData->id]);
            $EL = $this->SIAPRepository->EditOutgoingLetter($r->all());
            if (!$EL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($EL['message'], 400);
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
