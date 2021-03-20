<?php
namespace App\Repositories\SIAP;

use App\Models\Account\UserPermission;
use App\Models\SIAP\ForwardIncomingLetter;
use App\Models\SIAP\IncomingLetter;
use Carbon\Carbon;

trait DispositionRepository
{
    public function AddNewLetter(array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, body is empty",
            ];
        }

        // Closure Func Check User Permission
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
            return $UP->first();
        };

        $ForwardTo = [];
        // Check User
        $CUP = $CheckUserPermission((int) $body["user_id"]);
        if (!is_object($CUP)) {
            return [
                "status" => false,
                "message" => "failed add new letter, user created doesnt have permission",
            ];
        }
        array_push($ForwardTo, [
            'incoming_letter_id' => 0,
            'user_id' => (int) $body["user_id"],
            'types' => 0, // Creator
            'comment' => null,
        ]);

        // Check Decision
        $CDP = $CheckUserPermission(0, true);
        if (!is_object($CDP)) {
            return [
                "status" => false,
                "message" => "failed add new letter, user decision doesnt have permission",
            ];
        }
        array_push($ForwardTo, [
            'incoming_letter_id' => 0,
            'user_id' => (int) $CDP->user_id,
            'types' => 1, // Decision
            'comment' => null,
        ]);

        // Check Responder
        $count = 0;
        foreach ($body['forward_to']['responders'] as $id) {
            $CRP = $CheckUserPermission((int) $id);
            if (is_object($CRP)) {
                array_push($ForwardTo, [
                    'incoming_letter_id' => 0,
                    'user_id' => (int) $id,
                    'types' => 2, // Responder
                    'comment' => null,
                ]);
            } else {
                $count++;
            }
        }
        if ($count > 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, user responder doesnt have permission",
            ];
        }

        // Set Format Dateline
        switch ($body["dateline"]) {
            case 'OneDay':
                $body["dateline"] = Carbon::now();
                break;
            case 'TwoDay':
                $body["dateline"] = Carbon::now()->addDays(2);
                break;
            case 'ThreeDay':
                $body["dateline"] = Carbon::now()->addDays(3);
                break;
            default:
                $body["dateline"] = Carbon::now()->addDays(3);
                break;
        }

        // Insert Incoming Letter
        $IL = IncomingLetter::create([
            'user_id' => $body["user_id"],
            'code' => time(),
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

        foreach (array_keys($ForwardTo) as $key) {
            $ForwardTo[$key]['incoming_letter_id'] = $IL->id;
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
        switch ($body["dateline"]) {
            case 'OneDay':
                $body["dateline"] = Carbon::now();
                break;
            case 'TwoDay':
                $body["dateline"] = Carbon::now()->addDays(2);
                break;
            case 'ThreeDay':
                $body["dateline"] = Carbon::now()->addDays(3);
                break;
            default:
                $body["dateline"] = Carbon::now()->addDays(3);
                break;
        }

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
