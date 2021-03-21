<?php
namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class UploadController extends Controller
{
    public function Handler(Request $r)
    {
        $this->validate($r, [
            'file' => 'required|mimes:jpg,jpeg,bmp,png|max:5000',
        ]);
        $file = $r->file("file");
        $path = base_path('/public/storage');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $filename = time() . "-" . $file->getClientOriginalName();
        $file->move($path, $filename);
        // $filename = Helpers::StrWithoutExtension($filename);

        return response([
            'file' => $filename,
            'url' => URL::to('/storage/' . $filename),
        ]);
    }
}
