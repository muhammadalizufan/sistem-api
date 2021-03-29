<?php
namespace App\Repositories\SIAP;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\User;
use App\Models\Account\UserPermission;
use App\Models\Extension\Category;
use App\Models\Extension\File;
use App\Models\SIAP\ForwardIncomingLetter;
use App\Models\SIAP\IncomingLetter;
use App\Models\SIAP\Tag;
use App\Models\SIAP\TagIncomingLetter;
use App\Repositories\Extension\ExtensionRepository;
use Carbon\Carbon;

trait DispositionRepository
{
    private $CodeUnique = "SIAP/SM/";
    private $ExtRepo;

    public function __construct()
    {
        $this->ExtRepo = new ExtensionRepository;
    }

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

        // Create When Category Not Found
        $C = Category::where(['name' => trim($body['cat_name']), 'type' => 1])->first();
        if (is_null($C)) {
            $C = Category::create([
                'name' => trim($body['cat_name']),
                'type' => 1, // Disposition Categories
            ]);
        }

        // Insert Incoming Letter
        $IL = IncomingLetter::create([
            'user_id' => $body["user_id"],
            'cat_id' => $C->id ?? 0,
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

        // Add User Activity
        $AA = $this->ExtRepo->AddActivity([
            'user_id' => $body['user_id'],
            'ref_type' => 1,
            'ref_id' => $IL->id,
            'action' => "AddDisposition",
            'message_id' => "Menambahkan surat disposisi baru",
            'message_en' => "Adding a new disposition letter",
        ]);
        if (!$AA['status']) {
            return [
                "status" => false,
                "message" => $AA['message'],
            ];
        }

        // Update Reference ID Files table
        $F = File::find($body['file_id']);
        $F->update([
            'ref_id' => $IL->id,
        ]);

        // Insert Tags When Tag Not Found
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
        $C = Category::where(['name' => trim($body['cat_name']), 'type' => 1])->first();
        if (is_null($C)) {
            $C = Category::create([
                'name' => trim($body['cat_name']),
                'type' => 1, // Disposition Categories
            ]);
        }
        $IL->update([
            'user_id' => $body["user_id"],
            'cat_id' => $C->id ?? 0,
            'title' => $body["title"],
            'from' => $body["from"],
            'dateline' => $body["dateline"],
            'file' => $body["file"],
            'desc' => $body["desc"],
            'note' => $body["note"],
        ]);

        // Add User Activity
        $AA = $this->ExtRepo->AddActivity([
            'user_id' => $body['user_id'],
            'ref_type' => 1,
            'ref_id' => $body['incoming_letter_id'],
            'action' => "EditDisposition",
            'message_id' => "Merubah surat disposisi",
            'message_en' => "Editing a disposition letter",
        ]);
        if (!$AA['status']) {
            return [
                "status" => false,
                "message" => $AA['message'],
            ];
        }

        // Update Reference ID Files table
        $F = File::find($body['file_id']);
        $F->update([
            'ref_id' => $IL->id,
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
            ->whereNotIn('user_id', \array_merge($ResponderUIDs, [
                $body['user_id'], $DecisionUID,
            ]))->delete();
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

        if (is_null($FIL->comment)) {
            // Add User Activity
            $AA = $this->ExtRepo->AddActivity([
                'user_id' => $body['user_id'],
                'ref_type' => 1,
                'ref_id' => $FIL->incoming_letter_id,
                'action' => "EditCommentDisposition",
                'message_id' => "Mengomentari surat disposisi",
                'message_en' => "Comment on disposition letter",
            ]);
            if (!$AA['status']) {
                return [
                    "status" => false,
                    "message" => $AA['message'],
                ];
            }
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
        $U = User::find($GetUserID);
        // Add User Activity
        $AA = $this->ExtRepo->AddActivity([
            'user_id' => $body['user_id'],
            'ref_type' => 1,
            'ref_id' => $FIL->incoming_letter_id,
            'action' => "SendDisposition",
            'message_id' => "Mengirim surat disposisi ke " . ($U->name ?? ""),
            'message_en' => "Send a disposition letter to " . ($U->name ?? ""),
        ]);
        if (!$AA['status']) {
            return [
                "status" => false,
                "message" => $AA['message'],
            ];
        }
        return [
            "status" => true,
        ];
    }
}
