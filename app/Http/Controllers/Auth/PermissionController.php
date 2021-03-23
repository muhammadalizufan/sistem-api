<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Libs\Helpers;
use App\Models\Account\Permission;
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

    public function GetAllPermissionRawHandler(Request $r)
    {
        return response([
            'data' => json_decode(File::get(app()->basePath("permissions.json"))),
        ], 200);
    }

    public function GetAllPermissionHandler(Request $r)
    {
        $AP = Permission::all()->map(function ($i) {
            return $i['name'];
        });
        return response($AP->toArray(), 200);
    }

    public function UpdatePermissionHandler(Request $r)
    {
        $P = json_decode(File::get(app()->basePath("permissions.json")), true);

        $Array = collect([]);
        foreach ($P as $key => $p) {
            // $Array->push($p['value']);
            Helpers::IterationPermissionChild($Array, $p['child']);
        }

        $AP = Permission::all()->map(function ($i) {
            return $i['name'];
        });
        if (count($AP) <= 0) {
            foreach ($Array->toArray() as $p) {
                $P = new Permission();
                $P->fill([
                    'name' => trim($p),
                    "is_active" => 1,
                ]);
                $P->save();
            }

            return response([
                "message" => "success import new permission",
            ], 200);
        }

        $NotEqualPermission = $AP->toBase()->diff($Array->toArray());
        if (count($NotEqualPermission) >= 0) {
            return response([
                "message" => "import a nothing permission",
            ], 200);
        }

        foreach ($NotEqualPermission as $nep) {
            $P = new Permission();
            $P->fill([
                'name' => trim($nep),
                "is_active" => 1,
            ]);
            $P->save();
        }

        return response([
            "message" => "success import new permission",
        ], 200);
    }
}
