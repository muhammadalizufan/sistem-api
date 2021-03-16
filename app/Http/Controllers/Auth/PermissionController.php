<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PermissionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function GetAllPermissionHandler(Request $r)
    {
        return response([
            'data' => json_decode(File::get(app()->basePath("permissions.json"))),
        ], 200);
    }
}
