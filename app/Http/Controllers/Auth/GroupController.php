<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\Group;
use App\Repositories\Account\AccountRepository;
use App\Services\Account\AccountManager;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    private $AccountRepository;
    private $AccountManager;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->AccountRepository = new AccountRepository;
        $this->AccountManager = new AccountManager;
    }

    public function GetGroupHandler(Request $r, ?int $id = null)
    {
        $G = new Group;
        if (is_null($id)) {
            $G = $G::paginate(20);
        } else {
            $G = $G->where('id', $id)->first();
        }
        return $G;
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

    public function AddGroupHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditGroupRule());
            $this->AccountManager->CheckPermissionList($r->permission);
            $AG = $this->AccountRepository->AddNewGroup($r->all());
            if (!$AG['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AG['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\PermissionNotFoundException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add group",
        ], 201);
    }

    public function EditGroupHandler(Request $r, int $id = 0)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditGroupRule());
            $r->request->add(['group_id' => $id]);
            $this->AccountManager->IsGroupExist(
                $this->AccountRepository->GetGroup($id)
            );
            $this->AccountManager->CheckPermissionList($r->permission);
            $EG = $this->AccountRepository->EditGroup($r->all());
            if (!$EG['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($EG['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\GroupNotFoundException $e) {
        } catch (\PermissionNotFoundException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success update group",
        ], 200);
    }
}
