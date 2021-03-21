<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\User;
use App\Repositories\Account\AccountRepository;
use App\Services\Account\AccountManager;
use App\Services\Auth\LoginManager;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $AccountRepository;
    private $AccountManager;
    private $LoginManager;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->AccountRepository = new AccountRepository;
        $this->AccountManager = new AccountManager;
        $this->LoginManager = new LoginManager;
    }

    public function GetUserHandler(Request $r, ?int $id = null)
    {
        $U = new User;
        if (is_null($id)) {
            $U = $U::select("id", "name", "email", "status")->paginate(20);
        } else {
            $U = $U->where('id', $id)->first();
        }
        return $U;
    }

    private static function AddEditUserRule(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|string|max:150',
            'group_id' => 'required|integer|min:1',
            'role_id' => 'required|integer|min:1',
            'status' => 'required|string|in:Active,InActive',
            'permission' => 'required|min:1|array',
            'permission.*' => 'required',
        ];
    }

    public function AddUserHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditUserRule());
            $this->AccountManager->ErrorWhenUserExist(
                $this->AccountRepository->GetUserByEmail($r)
            );
            $this->AccountManager->ErrorWhenGroupNotFound(
                $this->AccountRepository->GetGroup($r->group_id)
            );
            $this->AccountManager->ErrorWhenRoleNotFound(
                $this->AccountRepository->GetRole($r->role_id)
            );
            $this->AccountManager->CheckPermissionList($r->permission);
            $AU = $this->AccountRepository->AddNewUser($r->all());
            if (!$AU['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AU['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\UserExistException $e) {
        } catch (\GroupNotFoundException $e) {
        } catch (\RoleNotFoundException $e) {
        } catch (\PermissionNotFoundException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add user",
        ], 201);
    }

    public function EditUserHandler(Request $r, int $id = 0)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::AddEditUserRule());
            $r->request->add(['user_id' => $id]);
            $this->AccountManager->ErrorWhenUserNotFound(
                $this->AccountRepository->GetUser($id)
            );
            $this->AccountManager->ErrorWhenUserExist(
                $this->AccountRepository->GetUserByEmail($r, true)
            );
            $this->AccountManager->ErrorWhenGroupNotFound(
                $this->AccountRepository->GetGroup($r->group_id)
            );
            $this->AccountManager->ErrorWhenRoleNotFound(
                $this->AccountRepository->GetRole($r->role_id)
            );
            $this->AccountManager->CheckPermissionList($r->permission);
            $AU = $this->AccountRepository->EditUser($r->all());
            if (!$AU['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AU['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\UserExistException $e) {
        } catch (\UserNotFoundException $e) {
        } catch (\GroupNotFoundException $e) {
        } catch (\RoleNotFoundException $e) {
        } catch (\PermissionNotFoundException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success edit user",
        ], 200);
    }

    private static function ChangeUserPasswordRule(): array
    {
        // need field new_password_confirmation
        return [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6|max:32|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'new_password' => 'required|string|min:6|max:32|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'new_password_confirmation' => 'required_with:new_password|same:new_password',
        ];
    }

    public function ChangeUserPasswordHandler(Request $r)
    {
        try {
            ValidatorManager::ValidateJSON($r, self::ChangeUserPasswordRule());
            $U = $this->AccountRepository->GetUserByEmail($r);
            $this->AccountManager->ErrorWhenUserNotFound($U);
            $this->LoginManager->PasswordIsMatch($r->password, $U->password);
            $AU = $this->AccountRepository->ChangeUserPassword($r->all());
            if (!$AU['status']) {
                throw new \App\Exceptions\FailedAddEditGlobalException($AU['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\UserNotFoundException $e) {
        } catch (\IncorrectPasswordException $e) {
        } catch (\FailedAddEditGlobalException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success change password",
        ], 200);
    }
}
