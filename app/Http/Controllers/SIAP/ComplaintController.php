<?php
namespace App\Http\Controllers\SIAP;

use App\Http\Controllers\Controller;
use App\Repositories\SIAP\SIAPRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    private $SIAPRepo;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->SIAPRepo = new SIAPRepository;
    }

    public function AddNewComplaintHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, [

            ]);
            if (!$this->SIAPRepo->AddNewOutgoingLetter($r)) {
                throw new \App\Exceptions\FailedAddEditGlobalException("failed add new letter", 400);
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
