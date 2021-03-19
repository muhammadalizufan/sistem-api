<?php
namespace App\Exceptions;

class FailedAddEditGlobalException extends \Exception
{
    private $Code;
    private $Message;

    public function __construct(string $Message = "", int $Code = 0)
    {
        $this->Message = $Message;
        $this->Code = $Code;
    }

    public function render()
    {
        return Response([
            "api_version" => "1.0",
            "message" => $this->Message,
        ], $this->Code);
    }
}
