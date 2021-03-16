<?php
namespace App\Repositories\SIAP;

use App\Models\Account\UserPermission;
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

        $U = UserPermission::where('user_id', $body["user_id"])->with("Permission")
            ->whereHas("Permission", function ($query) {
                $query->where("name", "SIAP.Disposition");
            })->first();
        if (!is_object($U)) {
            return [
                "status" => false,
                "message" => "failed add new letter, user doesnt have permission",
            ];
        }

        // Set Dateline
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

        IncomingLetter::create([
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
        dd($body);
    }
}
