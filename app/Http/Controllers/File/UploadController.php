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
            'from' => 'required|integer|min:1',
        ]);
        $file = $r->file("file");

        $basepath = '/public/storage';
        $path = base_path($basepath);
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
            'ref_type' => $r->input('from', 0),
            'ref_id' => 0,
            'ext' => $ext,
            'path' => $basepath,
        ]);

        return response([
            'id' => $F->id ?? 0,
            'file' => $name,
            'url' => URL::to('/storage/' . $fullname),
        ]);
    }
}
