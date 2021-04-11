<?php
namespace App\Repositories\SIAP;

use App\Libs\Helpers;
use App\Models\Account\Permission;
use App\Models\Account\UserPermission;
use App\Models\Extension\Category;
use App\Models\SIAP\OutgoingLetter;
use App\Repositories\Extension\ExtensionRepository;
use Illuminate\Http\Request;

trait OutgoingLetterRepository
{

    private function RMScriptTagHTML(Request $r, $isAdd = true)
    {
        $original = $r->input('original_letter', '');
        $validate = $r->input('validated_letter', '');
        $r->merge(['original_letter' => !empty($original) ? Helpers::RMScriptTagHTML($original, true) : ""]);
        if (!$isAdd) {
            $r->merge(['validated_letter' => !empty($validate) ? Helpers::RMScriptTagHTML($validate, true) : ""]);
        }
    }

    public function AddNewOutgoingLetter(Request $r)
    {
        $C = Category::updateOrcreate([
            'name' => trim($r->input('cat_name', 0)),
        ]);

        $this->RMScriptTagHTML($r);

        $OL = OutgoingLetter::create(array_merge($r->all([
            'user_id',
            'title',
            'to',
            'agency',
            'address',
            'original_letter',
            'validated_letter',
            'note',
        ])), [
            'cat_id' => $C->id ?? 0,
            'code' => "SIAP/SK/" . time(),
            'status' => 0,
            'is_archive' => 0,
        ]);
        if (!is_object($OL)) {
            return false;
        }

        (new ExtensionRepository())->AddActivity([
            'user_id' => $r->UserData->id,
            'ref_type' => "OutgoingLetter",
            'ref_id' => $OL->id,
            'action' => "Add",
            'message_id' => "Menambahkan surat keluar baru",
            'message_en' => "Adding a new outgoing letter",
        ]);

        return true;
    }

    public function EditOutgoingLetter(Request $r)
    {
        $OL = OutgoingLetter::find($r->id);
        if (!is_object($OL)) {
            return false;
        }

        $TMsg = "";
        $status = 0;
        switch ($r->input('status', '')) {
            case 'Reject':
                $status = 2;
                $TMsg = "ditolak";
                break;
            case 'Process':
                $status = 0;
                $TMsg = "proses";
                break;
        }

        $this->RMScriptTagHTML($r, false);

        $OldStatus = $OL->status;

        $CreateActivity = function (string $messageId = "", string $messageEn = "") use ($r, $OL) {
            (new ExtensionRepository())->AddActivity([
                'user_id' => $r->input('user_id', 0),
                'ref_type' => "OutgoingLetter",
                'ref_id' => $OL->id,
                'action' => "Edit",
                'message_id' => $messageId,
                'message_en' => $messageEn,
            ]);
        };

        $PID = Permission::where(["name" => "SIAP.OutgoingLetter.Approver", "is_active" => 1])->first()->id ?? 0;
        $UP = UserPermission::where(["user_id" => $r->UserData->id, "permission_id" => $PID, "is_active" => 1])->first();

        if (is_object($UP)) {
            $C = Category::updateOrcreate([
                'name' => trim($r->input('cat_name', '')),
            ]);
            $Update = array_merge($r->all([
                'validated_letter',
                'note',
            ]), [
                'cat_id' => $C->id ?? 0,
                'status' => $status,
            ]);
            $CreateActivity("Melakukan validasi surat keluar", "Perform outgoing mail validation");
            if ($OldStatus != $status) {
                $TMsgEn = strtolower($r->status);
                $CreateActivity("Merubah status surat keluar menjadi {$TMsg}", "Change the outgoing mail status to {$TMsgEn}");
            }
        } else {
            $Update = array_merge($r->all([
                'title',
                'to',
                'agency',
                'address',
                'original_letter',
            ]), [
                'status' => 0,
            ]);
            $CreateActivity("Melakukan perubahan surat keluar", "Make changes to outgoing mail");
        }
        $OL->update($Update);

        return true;
    }
}
