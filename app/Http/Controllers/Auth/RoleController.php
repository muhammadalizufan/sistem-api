<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\Role;
use App\Repositories\Account\AccountRepository;
use App\Services\Account\AccountManager;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class RoleController extends Controller
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

    public function GetRoleHandler(Request $r, ?int $id = null)
    {
        $R = new Role;
        if (is_null($id)) {
            $R = $R::paginate(20);
        } else {
            $R = $R->where('id', $id)->first();
        }
        return $R;
    }

    private static function AddEditRoleRule(): array
    {
        return [
            'name' => 'required|string|max:100',
            'group_id' => 'required|integer|min:1',
            'status' => 'required|string|in:Active,InActive',
            'permission' => 'required|min:1|array',
            'permission.*' => 'required',
        ];
    }

    public function AddRoleHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditRoleRule());
            $this->AccountManager->IsGroupExist(
                $this->AccountRepository->GetGroup($r->group_id)
            );
            $this->AccountManager->CheckPermissionList($r->permission);
            $AR = $this->AccountRepository->AddNewRole($r->all());
            if (!$AR['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AR['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\GroupNotFoundException $e) {
        } catch (\PermissionNotFoundException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add role",
        ], 201);
    }

    public function EditRoleHandler(Request $r, int $id = 0)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditRoleRule());
            $r->request->add(['role_id' => $id]);
            $this->AccountManager->IsRoleExist(
                $this->AccountRepository->GetRole($id)
            );
            $this->AccountManager->IsGroupExist(
                $this->AccountRepository->GetGroup($r->group_id)
            );
            $this->AccountManager->CheckPermissionList($r->permission);
            $ER = $this->AccountRepository->EditRole($r->all());
            if (!$ER['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($ER['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\RoleNotFoundException $e) {
        } catch (\GroupNotFoundException $e) {
        } catch (\PermissionNotFoundException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success update role",
        ], 200);
    }
}
