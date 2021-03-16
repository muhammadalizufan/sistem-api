<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\Role;
use App\Repositories\Account\AccountRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class RoleController extends Controller
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

    public function GetRoleHandler(Request $r)
    {
        return Role::paginate(20);
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
            $AR = $this->AccountRepository->AddNewRole($r->all());
            if (!$AR['status']) {
                throw new \App\Exceptions\FailedAddEditRoleException($AR['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddRoleException $e) {
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
            $ER = $this->AccountRepository->EditRole($r->all());
            if (!$ER['status']) {
                throw new \App\Exceptions\FailedAddEditRoleException($ER['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddRoleException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success update role",
        ], 200);
    }
}
