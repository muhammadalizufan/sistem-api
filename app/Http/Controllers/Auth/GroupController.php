<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\Group;
use App\Repositories\Account\AccountRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    private $AccountRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->AccountRepository = new AccountRepository;
    }

    public function GetHandler(Request $r)
    {
        return Group::paginate(20);
    }

    public function GetPermissionHandler(Request $r)
    {
        return Group::paginate(20);
    }

    private static function AddEditGroupRule(): array
    {
        return [
            'name' => 'required|string|max:100',
            'status' => 'required|string|in:Active,InActive',
            'permission' => 'required|min:1|array',
            'permission.*' => 'required',
        ];
    }

    public function AddHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditGroupRule());
            $AG = $this->AccountRepository->AddNewGroup($r->all());
            if (!$AG['status']) {
                throw new \App\Exceptions\FailedAddEditGroupException($AG['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddGroupException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add group",
        ], 201);
    }

    public function EditHandler(Request $r, int $id = 0)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditGroupRule());
            $r->request->add(['group_id' => $id]);
            $EG = $this->AccountRepository->EditGroup($r->all());
            if (!$EG['status']) {
                throw new \App\Exceptions\FailedAddEditGroupException($EG['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddGroupException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success update group",
        ], 200);
    }
}
