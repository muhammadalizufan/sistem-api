<?php
namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Models\SIAP\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
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

    public function GetCategoryHandler(Request $r)
    {
        $C = new Category;
        $Name = $r->input("name", "");
        if (!empty($Name)) {
            $C = $C->where("name", $Name);
        }
        return $C->paginate(10);
    }
}
