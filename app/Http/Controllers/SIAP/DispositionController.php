<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
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

    public function GetInboxHandler(Request $r, ?int $id = null)
    {
        $IL = new ForwardIncomingLetter;
        if (is_null($id)) {
            $IL = $IL::with("IncomingLetter", "User", "Tags");
            $IL = collect($IL->paginate(20))->toArray();
            if (count($IL['data']) > 0) {
                $IL['data'] = collect($IL['data'])->map(function ($i) {
                    $i['tags'] = collect($i['tags'])->map(function ($i) {
                        return $i['tag']['name'];
                    });
                    return $i;
                });
            }
        } else {
            $IL = $IL::with("IncomingLetter", "User", "Tags")->where('id', $id)->first();
            if (is_object($IL)) {
                $IL = $IL->toArray();
                $IL['tags'] = collect($IL['tags'])->map(function ($i) {
                    return $i['tag']['name'];
                });
            }
        }
        return $IL;
    }

    public function GetLetterHandler(Request $r, ?int $id = null)
    {
        $IL = new IncomingLetter;
        if (is_null($id)) {
            $IL = $IL::with("User", "Category")->paginate(20);
        } else {
            $IL = $IL::with("User", "Category")->where('id', $id)->first();
        }
        return $IL;
    }

    private static function AddEditNewLetterRule(): array
    {
        return [
            'title' => 'required|string',
            'from' => 'required|string',
            'dateline' => 'required|string|in:OneDay,TwoDay,ThreeDay',
            'file' => 'required|string',
            'desc' => 'required|string',
            'note' => 'required|string',
            'cat_id' => 'required|integer|min:1',
            'tags' => 'required|array',
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
