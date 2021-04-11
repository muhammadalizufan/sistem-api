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

    public function GetDisposistionResponders(Request $r)
    {
        $lvl = $r->input('level', '');
        $GID = $r->input('group_id', 0);
        if (in_array($lvl, ["Z", "A", "B", "C", "D", "E"])) {
            $PID = Permission::where("name", "SIAP.Disposition.Level.{$lvl}")->where("is_active", 1)->first()->id;
            $UP = UserPermission::with("Role")
                ->select("user_id", "role_id", "group_id")
                ->where("permission_id", $PID)
                ->where("user_id", "!=", $r->UserData->id);

            if ($GID > 0 && $lvl == "D") {
                $UP = $UP->where("group_id", $r->group_id);
            }

            return $UP->get()->map(function ($i) {
                return [
                    "id" => $i['user_id'],
                    "name" => $i['role']['name'],
                ];
            });
        }

        $PLevels = [
            "SIAP.Disposition.Level.Z",
            "SIAP.Disposition.Level.A",
            "SIAP.Disposition.Level.B",
            "SIAP.Disposition.Level.C",
            "SIAP.Disposition.Level.D",
            "SIAP.Disposition.Level.E",
        ];
        $Privilages = [
            "Z" => [0 => "A", 1 => "B", 2 => "C", 3 => "D", 4 => "E"],
            "A" => [0 => "B", 1 => "C"],
            "B" => [0 => "B", 1 => "C"],
            "C" => [0 => "D"],
            "D" => [0 => "E"],
        ];

        foreach ($Privilages as $key => $v) {
            $Privilages[$key] = collect($Privilages[$key])->map(function ($i) {
                return "SIAP.Disposition.Level.{$i}";
            })->toArray();
        }

        $Ps = Permission::whereIn("name", $PLevels)->where("is_active", 1)->get();

        $PIDs = $Ps->map(function ($i) {
            return $i['id'];
        })->toArray();

        $UPID = UserPermission::where("user_id", $r->UserData->id)->whereIn("permission_id", $PIDs)->first()->permission_id ?? 0;
        $Code = substr(Permission::where("id", $UPID)->where("is_active", 1)->first()->name ?? "", -1);

        $PIDs = $Ps->map(function ($i) use ($Privilages, $Code) {
            return in_array($i['name'], $Privilages[$Code]) ? $i['id'] : null;
        })->toArray();

        $UP = UserPermission::with("Role")
            ->select("user_id", "role_id", "group_id")
            ->whereIn("permission_id", $PIDs)
            ->where("user_id", "!=", $r->UserData->id);

        if ($Code == "D") {
            $UP = $UP->where("group_id", $r->UserData->group->group_id);
        }

        return $UP->get()->map(function ($i) {
            return [
                "id" => $i['user_id'],
                "name" => $i['role']['name'],
            ];
        });
    }

    public function GetInboxHandler(Request $r, ?int $id = null)
    {
        $I = Inbox::with([
            "Disposition" => function ($q) use ($r) {
                $q->whereIn("status", $r->input("status", [0]));
                $q->where("is_archive", $r->input("archive", 0));
            },
            "User",
        ]);
        $I = $I->where("ref_type", "Disposition")->where("forward_to", $r->UserData->id);
        if (!is_null($id)) {
            $Payload = $I->where('id', $id)->first();
            if (is_object($Payload)) {
                $Payload = $Payload->toArray();
                $FID = Inbox::select("forward_to")->where([
                    "ref_id" => $Payload['ref_id'],
                    "ref_type" => "Disposition",
                ])->where("user_type", "LIKE", "%Decision%")->first()->forward_to;
                $D = Comment::with(["User"])->where([
                    "ref_id" => $Payload['ref_id'],
                    "ref_type" => "Disposition",
                    "created_by" => $FID ?? 0,
                ])->first();

                // User
                $Payload['user'] = [
                    'id' => $Payload['user']['id'],
                    'name' => $Payload['user']['name'],
                    'role_id' => $Payload['user']['role']['role']['id'],
                    'role_name' => $Payload['user']['role']['role']['name'],
                ];

                // Decision
                $Payload['decision'] = [
                    'id' => $D['id'],
                    'user_id' => $D['user']['id'],
                    'role_id' => $D['user']['role']['role_id'] ?? null,
                    'user_name' => $D['user']['name'] ?? null,
                    'role_name' => $D['user']['role']['role']['name'] ?? null,
                    'comment' => $D['comment'],
                ];

                // Responders
                $Payload['responders'] = Comment::with(["User", "Disposition"])->where([
                    "ref_id" => $Payload['ref_id'],
                    "ref_type" => "Disposition",
                ])->whereHas("Disposition", function ($q) {
                    $q->whereRaw("FIND_IN_SET('Responder', `inboxs`.`user_type`) != 0");
                })->where("created_by", "!=", $FID ?? 0)->get()->map(function ($i) {
                    return [
                        'id' => $i['id'],
                        'user_id' => $i['user']['id'],
                        'role_id' => $i['user']['role']['role_id'] ?? null,
                        'user_name' => $i['user']['name'] ?? null,
                        'role_name' => $i['user']['role']['role']['name'] ?? null,
                        'comment' => $i['comment'],
                    ];
                });

                // Supervisors
                $Payload['supervisors'] = Inbox::with(["User"])->where([
                    "ref_id" => $Payload['ref_id'],
                    "ref_type" => "Disposition",
                ])->where(function ($q) {
                    $q->whereRaw("FIND_IN_SET('Supervisor', `inboxs`.`user_type`) != 0");
                    $q->whereRaw("FIND_IN_SET('Decision', `inboxs`.`user_type`) = 0");
                    $q->whereRaw("FIND_IN_SET('Responder', `inboxs`.`user_type`) = 0");
                })->where("forward_to", "!=", $FID ?? 0)->get()->map(function ($i) {
                    return [
                        'id' => $i['user']['id'],
                        'name' => $i['user']['name'] ?? null,
                        'role_name' => $i['user']['role']['role']['name'] ?? null,
                    ];
                });
            }
            return response($Payload, 200);
        }
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

    private static function AddEditNewLetterRule(): array
    {
        return [
            'title' => 'required|string',
            'from' => 'required|string',
            'dateline' => 'required|string|in:OneDay,TwoDay,ThreeDay',
            'cat_name' => 'required|string',
            'file_id' => 'required|integer|min:1',
            'desc' => 'string',
            'note' => 'string',
            'tags' => 'array',
            'private' => 'required|boolean',
            'users_decision' => 'required|integer|min:1', // user id decision
            'users_responders' => 'required|min:1|array',
            'users_responders.*' => 'required', // user id responder
        ];
    }

    public function AddNewLetterHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditNewLetterRule());
            if (!$this->SIAPRepository->AddNewLetter($r)) {
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
            ValidatorManager::ValidateJSON($r, self::AddEditNewLetterRule());
            $r->request->add(['id' => $id]);
            if (!$this->SIAPRepository->EditLetter($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed edit a letter", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success edit a letter",
        ], 200);
    }

    public function CommentLetterHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'comment_id' => 'required|integer|min:1',
                'comment' => 'required|string',
            ]);
            if (!$this->SIAPRepository->CommentLetter($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed comment a letter", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success comment a letter",
        ], 200);
    }

    public function SendLetterHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [
                'disposition_id' => 'required|integer|min:1',
                'to' => 'required|integer|min:1',
            ]);
            if (!$this->SIAPRepository->SendLetter($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed send a letter", 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success send a letter",
        ], 200);
    }
}
