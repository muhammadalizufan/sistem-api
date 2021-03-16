<?php
namespace App\Exceptions;

use Exception;

class ValidateException extends Exception
{
    private $Errors;
    public function __construct(array $Errors = [])
    {
        $this->Errors = $Errors;
    }
    public function render()
    {
        return response()->json($this->Errors, 422);
    }
}
