<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\SIAP\ForwardIncomingLetter;
use App\Models\SIAP\IncomingLetter;
use App\Repositories\SIAP\SIAPRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class DispositionController extends Controller
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

    public function GetDisposistionResponders(Request $r, ?int $level = null)
    {
        $P = new Permission;
        $UP = UserPermission::with("Role");

        if (is_null($level)) {
            $PID = $P->where("name", "SIAP.Disposition.Responders")->first()->id ?? 0;
            $UP = $UP->where("permission_id", $PID)->select("role_id")->distinct("role_id")->get();
        } else {
            $PID = $P->where("name", "SIAP.Disposition.Level.$level")->first()->id ?? 0;
            $UP = $UP->where("permission_id", $PID)->select("role_id")->distinct("role_id")->get();
        }

        return $UP->map(function ($i) {
            return [
                "id" => $i['role_id'],
                "name" => $i['role']['name'],
            ];
        });
    }

    public function GetInboxHandler(Request $r, ?int $id = null)
    {
        $limit = 10;
        if (\is_int($r->input('limit'))) {
            $limit = $r->input('limit', 10);
        }

        $IL = ForwardIncomingLetter::with([
            "IncomingLetter" => function ($q) {
                $q->with(['ForwardIncomingLetters' => function ($qTwo) {
                    $qTwo->where('types', 2);
                }, "File"]);
            },
            "User",
            "Tags",
        ]);

        $IL = $IL->whereHas("IncomingLetter", function ($q) use ($r) {
            if ($r->has("status") && !empty($r->input("status"))) {
                $q->where("status", $r->input("status", 0));
            }
            $q->where("is_archive", 0);
        });

        // Remove Receive Letter
        if (is_object($r->UserData)) {
            $UID = $r->UserData->id;
            $IL = $IL->where("user_id", $UID);
            if (is_object(ForwardIncomingLetter::where("user_id", $UID)->where("types", 3)->first())) {
                $IL = $IL->where("types", "!=", 3);
            }
        }

        //  Map Payload Data Pagination
        if (is_null($id)) {
            $IL = collect($IL->paginate($limit))->toArray();
            if (count($IL['data']) > 0) {
                $IL['data'] = collect($IL['data'])->map(function ($i) {
                    $i['tags'] = collect($i['tags'])->map(function ($i) {
                        return $i['tag']['name'];
                    });
                    $i['incoming_letter']['forward_incoming_letters'] = collect($i['incoming_letter']['forward_incoming_letters'])->map(function ($i) {
                        return [
                            'role_id' => $i['user']['role']['role_id'],
                            'role_name' => $i['user']['role']['role']['name'],
                            'comment' => $i['comment'],
                            'updated_at' => $i['updated_at'],
                        ];
                    });
                    return $i;
                });
            }
        } else {
            $IL = $IL->where('id', $id)->first();
            if (is_object($IL)) {
                $IL = $IL->toArray();
                $IL['tags'] = collect($IL['tags'])->map(function ($i) {
                    return $i['tag']['name'];
                });
                $IL['incoming_letter']['forward_incoming_letters'] = collect($IL['incoming_letter']['forward_incoming_letters'])->map(function ($i) {
                    return [
                        'role_id' => $i['user']['role']['role_id'],
                        'role_name' => $i['user']['role']['role']['name'],
                        'comment' => $i['comment'],
                        'updated_at' => $i['updated_at'],
                    ];
                });
            }
        }
        return $IL;
    }

    public function GetLetterHandler(Request $r, ?int $id = null)
    {
        $limit = 10;
        if (\is_int($r->input('limit'))) {
            $limit = $r->input('limit', 10);
        }

        $IL = IncomingLetter::with(["User", "Category", "ForwardIncomingLetters" => function ($q) use ($r) {
            $q->where("user_id", "!=", $r->UserData->id);
        }]);

        if ($r->has("status") && !empty($r->input("status"))) {
            $IL = $IL->where("status", $r->input("status", 0));
        }
        $IL = $IL->where("is_archive", $r->input("is_archive", 0));

        $MapFILs = function ($FIL) {
            return collect($FIL)->map(function ($i) {
                return [
                    "user" => $i['user'],
                    "comment" => $i['comment'],
                    'created_at' => $i['created_at'],
                    'updated_at' => $i['updated_at'],
                ];
            });
        };

        if (is_null($id)) {
            $IL = collect($IL->paginate($limit))->toArray();
            $IL['data'] = collect($IL['data'])->map(function ($i) use ($MapFILs) {
                $i['forward_incoming_letters'] = $MapFILs($i['forward_incoming_letters'] ?? []);
                return $i;
            });
        } else {
            $IL = $IL->where('id', $id)->first();
            if (is_object($IL)) {
                $IL = $IL->toArray();
                $IL['forward_incoming_letters'] = $MapFILs($IL['forward_incoming_letters'] ?? []);
            }
        }
        return $IL;
    }

    private static function AddEditNewLetterRule(): array
    {
        return [
            'title' => 'required|string',
            'from' => 'required|string',
            'dateline' => 'required|string|in:OneDay,TwoDay,ThreeDay',
            'cat_name' => 'required|string',
            'file' => 'required|string',
            'file_id' => 'required|integer|min:1',
            'desc' => 'string',
            'note' => 'string',
            'tags' => 'array',
            'forward_to' => 'required',
            'forward_to.responders' => 'required|min:1|array',
            'forward_to.responders.*' => 'required',
        ];
    }

    public function AddNewLetterHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditNewLetterRule());
            $r->request->add(['user_id' => $r->UserData->id]);
            $AL = $this->SIAPRepository->AddNewLetter($r->all());
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
            ValidatorManager::ValidateJSON($r, self::AddEditNewLetterRule());
            $r->request->add(['incoming_letter_id' => $id, 'user_id' => $r->UserData->id]);
            $EL = $this->SIAPRepository->EditLetter($r->all());
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

    public function CommentLetterHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'comment' => 'required|string',
            ]);
            $r->request->add(['forward_incoming_letter_id' => $id, 'user_id' => $r->UserData->id]);
            $CL = $this->SIAPRepository->CommentLetter($r->all());
            if (!$CL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($CL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success comment letter",
        ], 200);
    }

    public function SendLetterHandler(Request $r, ?int $id = null)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'send_to' => 'required|integer|min:1',
            ]);
            $r->request->add(['incoming_letter_id' => $id, 'user_id' => $r->UserData->id]);
            $CL = $this->SIAPRepository->SendLetter($r->all());
            if (!$CL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($CL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success send letter",
        ], 200);
    }
}
