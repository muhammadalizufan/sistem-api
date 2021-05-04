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
            'files' => 'required', // anything but max size: 15 MB
            'files.*' => 'required|max:15360',
        ]);

        $basepath = '/public/storage';
        $date = date("Y-m-d");
        $path = base_path($basepath . "/" . $date);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $data = [];
        foreach ($r->file("files") as $file) {
            $ext = $file->extension();
            $name = time() . "-" . Helpers::QuickRandom();
            $fullname = $name . ".$ext";
            $file->move($path, $fullname);
            array_push($data, [
                "ext" => $ext,
                "name" => $name,
                "fullname" => $fullname,
            ]);
        }

        $result = [];
        foreach ($data as $k => $v) {
            $F = File::create([
                'name' => $v['name'],
                'fullname' => $v['fullname'],
                'ref_type' => "",
                'ref_id' => 0,
                'ext' => $v['ext'],
                'path' => $basepath . "/" . $date,
            ]);
            array_push($result, [
                'id' => $F->id ?? 0,
                'name' => $v['name'],
                'file' => $v['fullname'],
                'url' => URL::to('/storage/' . $date . '/' . $v['fullname']),
            ]);
        }

        return response($result, 200);
    }
}
