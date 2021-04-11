<?php
namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Libs\Helpers;
use App\Models\Extension\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class UploadController extends Controller
{
    public function Handler(Request $r)
    {
        $this->validate($r, [
            'file' => 'required|max:15360', // anything but max size: 15 MB
        ]);
        $file = $r->file("file");

        $basepath = '/public/storage';
        $date = date("Y-m-d");
        $path = base_path($basepath . "/" . $date);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $ext = $file->extension();
        $name = time() . "-" . Helpers::QuickRandom();
        $fullname = $name . ".$ext";
        $file->move($path, $fullname);

        $F = File::create([
            'name' => $name,
            'fullname' => $fullname,
            'ref_type' => "",
            'ref_id' => 0,
            'ext' => $ext,
            'path' => $basepath,
        ]);

        return response([
            'id' => $F->id ?? 0,
            'name' => $name,
            'file' => $fullname,
            'url' => URL::to('/storage/' . $date . '/' . $fullname),
        ]);
    }
}
