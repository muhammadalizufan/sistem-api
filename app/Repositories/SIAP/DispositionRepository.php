<?php
namespace App\Repositories\SIAP;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\SIAP\ForwardIncomingLetter;
use App\Models\SIAP\IncomingLetter;
use Carbon\Carbon;

trait DispositionRepository
{
    // check user has add permission on desposition.add
    public function AddNewLetter(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, body is empty",
            ];
        }
        $ForwardTo = collect([]);
        $GetUserID = (function () use ($body): ?array{
            $Responder = Permission::where(["name" => "SIAP.Disposition.Responders", "is_active" => 1])->first()->id ?? 0;
            $Decision = Permission::where(["name" => "SIAP.Disposition.Decision", "is_active" => 1])->first()->id ?? 0;
            return [
                "Responder" => UserPermission::where([
                    "role_id" => $body['forward_to']['responders'],
                    "permission_id" => $Responder,
                ])->get()->map(function ($i) {
                    return $i['user_id'];
                })->toArray(),
                "Decision" => UserPermission::where([
                    "permission_id" => $Decision,
                ])->first()->id ?? 0,
            ];
        })();
        $Responders = $GetUserID['Responder'];
        $Decision = $GetUserID['Decision'];
        if ($Responders <= 0 || $Decision == 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, user with role is empty",
            ];
        }
        $ForwardTo->push([
            'incoming_letter_id' => 0,
            'user_id' => (int) $body["user_id"],
            'types' => 0, // Creator
            'comment' => null,
        ]);
        // Set Format Dateline
        $body = Helpers::ConvertDatelineBodyToDate($body);
        // Insert Incoming Letter
        $IL = IncomingLetter::create([
            'user_id' => $body["user_id"],
            'cat_id' => $body["cat_id"],
            'code' => "SIAP/SM/SD/" . time(),
            'title' => $body["title"],
            'from' => $body["from"],
            'date' => Carbon::now(),
            'dateline' => $body["dateline"],
            'file' => $body["file"],
            'desc' => $body["desc"],
            'note' => $body["note"],
            'status' => 0, // Set Status to 0 (Process)
            'is_archive' => 0,
        ]);
        if (!is_object($IL)) {
            return [
                "status" => false,
                "message" => "failed add new letter",
            ];
        }

        $ForwardTo->push([
            'incoming_letter_id' => 0,
            'user_id' => (int) $Decision,
            'types' => 1, // Decision
            'comment' => null,
        ]);
        foreach ($Responders as $key) {
            $ForwardTo->push([
                'incoming_letter_id' => 0,
                'user_id' => (int) $key,
                'types' => 2, // Responder
                'comment' => null,
            ]);
        }
        $ForwardTo = $ForwardTo->toArray();
        foreach (array_keys($ForwardTo) as $key) {
            $ForwardTo[$key]['incoming_letter_id'] = $IL->id;
            $ForwardTo[$key]['created_at'] = Carbon::now();
            $ForwardTo[$key]['updated_at'] = Carbon::now();
        }

        $FIL = ForwardIncomingLetter::insert($ForwardTo);
        if (!$FIL) {
            IncomingLetter::where('id', $IL->id)->forceDelete();
            return [
                "status" => false,
                "message" => "failed add new letter",
            ];
        }

        return [
            "status" => true,
        ];
    }

    public function EditLetter(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, body is empty",
            ];
        }

        $CheckUserPermission = function (int $userId = 0, bool $IsDecision = false) {
            $UP = new UserPermission;
            if (!$IsDecision) {
                $UP = $UP->where('user_id', $userId);
            }
            $UP = $UP->with("Permission")->whereHas("Permission", function ($query) use ($IsDecision) {
                if ($IsDecision) {
                    $query = $query->where("name", "SIAP.Disposition.Decision");
                } else {
                    $query = $query->where("name", "SIAP.Disposition");
                }
                $query = $query->where("is_active", 1);
                return $query;
            });
            if ($IsDecision) {
                return $UP->get();
            }
            return $UP->first();
        };

        // Check User
        $CUP = $CheckUserPermission((int) $body["user_id"]);
        if (!is_object($CUP)) {
            return [
                "status" => false,
                "message" => "failed add new letter, user doesnt have permission",
            ];
        }

        // Check Decision
        $CDP = $CheckUserPermission(0, true);
        if (!is_object($CDP)) {
            return [
                "status" => false,
                "message" => "failed add new letter, user decision doesnt have permission",
            ];
        }

        // Check Responder
        $count = 0;
        foreach ($body['forward_to']['responders'] as $i) {
            $CRP = $CheckUserPermission((int) $i);
            if (!is_object($CRP)) {
                $count++;
            }
        }
        if ($count > 0) {
            return [
                "status" => false,
                "message" => "failed update letter, user responder doesnt have permission",
            ];
        }

        $UserIDS = [];
        $UserIDS = array_merge(array_push($UserIDS, $body["user_id"], $CDP->user_id), $body['forward_to']['responders']);

        // Set Format Dateline
        $body = Helpers::ConvertDatelineBodyToDate($body);

        // Insert Incoming Letter
        $IL = IncomingLetter::find($body['incoming_letter_id']);
        $IL->update([
            'user_id' => $body["user_id"],
            'title' => $body["title"],
            'from' => $body["from"],
            'dateline' => $body["dateline"],
            'file' => $body["file"],
            'desc' => $body["desc"],
            'note' => $body["note"],
            'status' => 0, // Set Status to 0 (Process)
            'is_archive' => 0,
        ]);
        if (!is_object($IL)) {
            return [
                "status" => false,
                "message" => "failed update letter",
            ];
        }

        $FIL = ForwardIncomingLetter::where('incoming_letter_id', $IL->id)->get()
            ->map(function ($i) {
                return $i['user_id'];
            });

        $UserIDNotEqual = $FIL->diff($UserIDS);
        foreach ($UserIDNotEqual as $id) {
            ForwardIncomingLetter::where([
                'incoming_letter_id' => $IL->id,
                'user_id' => $id,
            ])->delete();
        }

        return [
            "status" => true,
        ];
    }
}
