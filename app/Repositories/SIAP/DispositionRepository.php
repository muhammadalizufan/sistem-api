<?php
namespace App\Repositories\SIAP;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\SIAP\ForwardIncomingLetter;
use App\Models\SIAP\IncomingLetter;
use App\Models\SIAP\Tag;
use App\Models\SIAP\TagIncomingLetter;
use Carbon\Carbon;

trait DispositionRepository
{
    private $CodeUnique = "SIAP/SM/";

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
        $GetUserID = (function () use ($body): ?array{
            $PRID = Permission::where(["name" => "SIAP.Disposition.Responders", "is_active" => 1])->first()->id ?? 0;
            $PDID = Permission::where(["name" => "SIAP.Disposition.Decision", "is_active" => 1])->first()->id ?? 0;
            return [
                "Responders" => UserPermission::whereIn("role_id", $body['forward_to']['responders'])
                    ->where("permission_id", $PRID)->get()
                    ->map(function ($i) {
                        return $i['user_id'];
                    })
                    ->toArray(),
                "Decision" => UserPermission::where("permission_id", $PDID)->first()->user_id ?? 0,
            ];
        })();
        $ResponderUIDs = $GetUserID['Responders'];
        $DecisionUID = $GetUserID['Decision'];
        if ($ResponderUIDs <= 0 || $DecisionUID == 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, user with role is empty",
            ];
        }
        // Set Format Dateline
        $body = Helpers::ConvertDatelineBodyToDate($body);
        // Insert Incoming Letter
        $IL = IncomingLetter::create([
            'user_id' => $body["user_id"],
            'cat_id' => $body["cat_id"],
            'code' => $this->CodeUnique . time(),
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
        if (count($body['tags']) > 0) {
            foreach ($body['tags'] as $tag) {
                $T = Tag::where('name', trim($tag));
                if (is_null($T->first())) {
                    $T = Tag::create([
                        "name" => trim($tag),
                    ]);
                    if (is_object($T)) {
                        TagIncomingLetter::create([
                            'incoming_letter_id' => $IL->id,
                            'tag_id' => $T->id,
                        ]);
                    }
                } else {
                    if (is_object($T->first())) {
                        TagIncomingLetter::create([
                            'incoming_letter_id' => $IL->id,
                            'tag_id' => $T->id,
                        ]);
                    }
                }
            }
        }

        $MergeWithDate = function (array $array = []): array{
            return array_merge($array, [
                'comment' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        };
        $ForwardTo = collect([
            $MergeWithDate([
                'incoming_letter_id' => $IL->id,
                'user_id' => (int) $body["user_id"],
                'types' => 0, // Creator
            ]),
            $MergeWithDate([
                'incoming_letter_id' => $IL->id,
                'user_id' => (int) $DecisionUID,
                'types' => 1, // Decision
            ])]);

        foreach ($ResponderUIDs as $id) {
            $ForwardTo->push(
                $MergeWithDate([
                    'incoming_letter_id' => $IL->id,
                    'user_id' => (int) $id,
                    'types' => 2, // Responder
                ])
            );
        }

        $FIL = ForwardIncomingLetter::insert($ForwardTo->toArray());
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
                "message" => "failed update letter, body is empty",
            ];
        }
        $GetUserID = (function () use ($body): ?array{
            $PRID = Permission::where(["name" => "SIAP.Disposition.Responders", "is_active" => 1])->first()->id ?? 0;
            $PDID = Permission::where(["name" => "SIAP.Disposition.Decision", "is_active" => 1])->first()->id ?? 0;
            return [
                "Responders" => UserPermission::whereIn("role_id", $body['forward_to']['responders'])
                    ->where("permission_id", $PRID)->get()
                    ->map(function ($i) {
                        return $i['user_id'];
                    })
                    ->toArray(),
                "Decision" => UserPermission::where("permission_id", $PDID)->first()->user_id ?? 0,
            ];
        })();
        $ResponderUIDs = $GetUserID['Responders'];
        $DecisionUID = $GetUserID['Decision'];
        if ($ResponderUIDs <= 0 || $DecisionUID == 0) {
            return [
                "status" => false,
                "message" => "failed update letter, user with role is empty",
            ];
        }
        // Set Format Dateline
        $body = Helpers::ConvertDatelineBodyToDate($body);
        // Update Incoming Letter
        $IL = IncomingLetter::find($body['incoming_letter_id']);
        if (!is_object($IL)) {
            return [
                "status" => false,
                "message" => "failed update letter",
            ];
        }
        // Check Letter Status Has been Success / Failed Cannot be updated
        if (in_array($IL->status, [1, 2])) {
            return [
                "status" => false,
                "message" => "failed update letter, has been complete.",
            ];
        }
        $IL->update([
            'user_id' => $body["user_id"],
            'cat_id' => $body["cat_id"],
            'title' => $body["title"],
            'from' => $body["from"],
            'dateline' => $body["dateline"],
            'file' => $body["file"],
            'desc' => $body["desc"],
            'note' => $body["note"],
        ]);
        // Add Or Restore Forward Incoming Letter Responder
        foreach ($ResponderUIDs as $id) {
            $FIL = ForwardIncomingLetter::withTrashed()
                ->where(['incoming_letter_id' => $body['incoming_letter_id'], 'user_id' => $id]);
            if (is_object($FIL->first())) {
                $FIL->update([
                    'comment' => null,
                ]);
                $FIL->restore();
            } else {
                ForwardIncomingLetter::create([
                    'incoming_letter_id' => $body['incoming_letter_id'],
                    'user_id' => (int) $id,
                    'types' => 2, // Responder
                    'comment' => null,
                ]);
            }
        }
        // Delete Forward Incoming Letter
        $FIL = ForwardIncomingLetter::where('incoming_letter_id', $body['incoming_letter_id'])
            ->whereNotIn('user_id', $ResponderUIDs)
            ->delete();
        if (!$FIL) {
            return [
                "status" => false,
                "message" => "failed update letter",
            ];
        }
        return [
            "status" => true,
        ];
    }

    public function CommentLetter(?array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed comment letter, body is empty",
            ];
        }
        // user_id by token user -> check permission, open inbox, ke detail, tulis komen, send
        $FIL = ForwardIncomingLetter::find($body['forward_incoming_letter_id']);
        if (!is_object($FIL)) {
            return [
                "status" => false,
                "message" => "failed comment letter",
            ];
        }
        $FIL->update([
            'comment' => $body['comment'],
        ]);
        return [
            "status" => true,
        ];
    }

    public function SendLetter(?array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed send letter, body is empty",
            ];
        }
        $GetUserID = (function () use ($body): ?int {
            $PID = Permission::where(["name" => "SIAP.Disposition", "is_active" => 1])->first()->id ?? 0;
            return UserPermission::where("role_id", $body['send_to'])->where("permission_id", $PID)->first()->user_id ?? 0;
        })();
        $FIL = ForwardIncomingLetter::create([
            'incoming_letter_id' => $body['incoming_letter_id'],
            'user_id' => (int) $GetUserID,
            'types' => 3, // Receiver
            'comment' => null,
        ]);
        if (!$FIL) {
            return [
                "status" => false,
                "message" => "failed send letter",
            ];
        }
        $IL = IncomingLetter::find($body['incoming_letter_id']);
        $IL->update([
            'status' => 1,
        ]);
        return [
            "status" => true,
        ];
    }
}
