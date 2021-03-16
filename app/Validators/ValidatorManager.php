<?php
namespace App\Validators;

use App\Libs\Helpers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Exceptions\ValidateException;

class ValidatorManager
{
    public static function ValidateJSON(Request $request, array $rule)
    {
        $Validator = Validator::make($request->only(array_keys($rule)), $rule);
        if ($Validator->fails()) {
            throw new ValidateException(
                Helpers::MapErrorsValidator($Validator->errors()->toArray())
            );
        }
    }
}
