<?php
namespace App\Repositories\SIAP;

use App\Libs\Helpers;
use App\Models\Extension\Category;
use App\Models\SIAP\OutgoingLetter;
use App\Repositories\Extension\ExtensionRepository;

trait OutgoingLetterRepository
{
    public function AddNewOutgoingLetter(?array $body = null)
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add new letter, body is empty",
            ];
        }

        // Create When Category Not Found
        $C = Category::where(['name' => trim($body['cat_name']), 'type' => 2])->first();
        if (is_null($C)) {
            $C = Category::create([
                'name' => trim($body['cat_name']),
                'type' => 2, // Outgoing Letter Categories
            ]);
        }

        // Filter HTML String
        $body = $this->RMScriptTagHTML($body);

        $OL = OutgoingLetter::create([
            'user_id' => $body['user_id'],
            'cat_id' => $C->id ?? 0,
            'code' => "SIAP/SK/" . time(),
            'title' => $body['title'],
            'to' => $body['to'],
            'agency' => $body['agency'],
            'address' => $body['address'],
            'original_letter' => $body['original_letter'],
            'validated_letter' => null,
            'note' => $body['note'],
            'status' => 0,
            'is_archive' => 0,
        ]);
        if (!is_object($OL)) {
            return [
                "status" => false,
                "message" => "failed add new letter",
            ];
        }

        // Add User Activity
        $AA = (new ExtensionRepository())->AddActivity([
            'user_id' => $body['user_id'],
            'ref_type' => 2, // Outgoing Letter
            'ref_id' => $OL->id,
            'action' => "AddOutgoingLetter",
            'message_id' => "Menambahkan surat keluar baru",
            'message_en' => "Adding a new outgoing letter",
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

    private function RMScriptTagHTML($body, $isAdd = true)
    {
        $body['original_letter'] = Helpers::RMScriptTagHTML($body['original_letter'], true);
        if (!$isAdd) {
            $body['validated_letter'] = Helpers::RMScriptTagHTML($body['validated_letter'], true);
        }
        return $body;
    }

    public function EditOutgoingLetter(?array $body = null)
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed edit letter, body is empty",
            ];
        }

        // Create When Category Not Found
        $C = Category::where(['name' => trim($body['cat_name']), 'type' => 2])->first();
        if (is_null($C)) {
            $C = Category::create([
                'name' => trim($body['cat_name']),
                'type' => 2, // Outgoing Letter Categories
            ]);
        }

        // Chnage Body Status
        $status = 0;
        $translateMsg = "";
        switch ($body['status']) {
            case 'Approve':
                $status = 1;
                $translateMsg = "diterima";
                break;
            case 'Reject':
                $status = 2;
                $translateMsg = "ditolak";
                break;
            case 'Process':
                $status = 0;
                $translateMsg = "proses";
                break;
        }

        // Filter HTML String
        $body = $this->RMScriptTagHTML($body, false);

        $OL = OutgoingLetter::find($body['outcoming_letter_id']);

        $OldStatus = $OL->status;
        $OldValidatedLetter = $OL->validated_letter;

        $CreateActivity = function (string $messageId = "", string $messageEn = "") use ($body, $OL) {
            (new ExtensionRepository())->AddActivity([
                'user_id' => $body['user_id'],
                'ref_type' => 2, // Outgoing Letter
                'ref_id' => $OL->id,
                'action' => "EditOutgoingLetter",
                'message_id' => $messageId,
                'message_en' => $messageEn,
            ]);
        };

        $OL->update([
            'cat_id' => $C->id ?? 0,
            'title' => $body['title'],
            'to' => $body['to'],
            'agency' => $body['agency'],
            'original_letter' => $body['original_letter'],
            'validated_letter' => $body['validated_letter'],
            'note' => $body['note'],
            'status' => $status,
        ]);

        // Set Activity User
        $compareString = strcmp($body['original_letter'], $body['validated_letter']);

        // messages
        $MsgIDFirst = "Melakukan validasi surat keluar";
        $MsgENFirst = "Perform outgoing mail validation";
        $MsgIDSecond = "Melakukan perubahan surat keluar";
        $MsgENSecond = "Make changes to outgoing mail";

        if (!is_null($OldValidatedLetter)) {
            if ($compareString >= 0) {
                $CreateActivity($MsgIDFirst, $MsgENFirst);
            } else {
                $CreateActivity($MsgIDSecond, $MsgENSecond);
            }
        } else {
            if ($compareString > 0) {
                $CreateActivity($MsgIDFirst, $MsgENFirst);
            }
            if ($compareString < 0) {
                $CreateActivity($MsgIDSecond, $MsgENSecond);
            }
        }

        // if old status letter edited and cannot same status
        if ($OldStatus != $status) {
            $translateMsgEn = \strtolower($body['status']);
            $CreateActivity("Merubah status surat keluar menjadi {$translateMsg}", "Change the outgoing mail status to {$translateMsgEn}");
        }

        return [
            "status" => true,
        ];
    }
}
