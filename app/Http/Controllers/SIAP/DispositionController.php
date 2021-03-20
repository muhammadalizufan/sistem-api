<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
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

    public function GetLetterHandler(Request $r, ?int $id = null)
    {
        $IL = new IncomingLetter;
        if (is_null($id)) {
            $IL = $IL::with("User")->paginate(20);
        } else {
            $IL = $IL::with("User")->where('id', $id)->first();
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

    public function EditLetterHandler(Request $r, int $id = 0)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditNewLetterRule());
            $r->request->add(['incoming_letter_id' => $id, 'user_id' => $r->UserData->id]);
            $AL = $this->SIAPRepository->EditLetter($r->all());
            if (!$AL['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AL['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add new letter",
        ], 201);
    }

}
