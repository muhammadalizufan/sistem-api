<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Account\AccountRepository;
use App\Validators\ValidatorManager;
use Illuminate\Http\Request;

class UserController extends Controller
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

    private static function AddUserRule(): array
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
            ValidatorManager::ValidateJSON($r, self::AddUserRule());
            $AddUser = $this->AccountRepository->AddNewUser($r->all());
            if (!$AddUser['status']) {
                throw new \App\Exceptions\FailedAddRoleException($AddUser['message'], 400);
            }
        } catch (\ValidateException $e) {
        } catch (\FailedAddRoleException $e) {
        }
        return response([
            "api_version" => "1.0",
            "message" => "success add user",
        ], 201);
    }
}
