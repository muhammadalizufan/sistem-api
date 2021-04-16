<?php
namespace App\Exceptions;

class SingleErrorException extends \Exception
{
    private $Code, $Message, $Reason;

    public function __construct(string $Message = "", int $Code = 0)
    {
        $this->Message = $Message;
        $this->Code = $Code;
        $this->GenerateReason();
    }

    private function GenerateReason()
    {
        switch ($this->Code) {
            case 401:
                $this->Reason = "Unauthorized";
                break;
            case 400:
                $this->Reason = "BadRequest";
                break;
            case 422:
                $this->Reason = "UnprocessableEntity";
                break;
            default:
                $this->Reason = "Unauthorized";
                break;
        }
    }

    public function render()
    {
        return Response([
            'api_version' => "1.0",
            "error" => [
                'code' => $this->Code,
                "message" => $this->Reason,
                "reason" => $this->Reason . "Exception",
                "errors" => [
                    [
                        "domain" => null,
                        "reason" => $this->Reason . "Exception",
                        "message" => $this->Message,
                    ],
                ],
            ],
        ], $this->Code);
    }
}
