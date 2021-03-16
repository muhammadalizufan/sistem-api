<?php
namespace App\Exceptions;

class UnauthorizedException extends \Exception
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
            'api_version' => "1.0",
            "error" => [
                'code' => 401,
                "message" => "Unauthorized",
                "reason" => "UnauthorizedException",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => "UnauthorizedException",
                        "message" => $this->Message,
                    ],
                ],
            ],
        ], $this->Code);
    }
}
