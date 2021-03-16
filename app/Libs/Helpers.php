<?php
namespace App\Libs;

use Illuminate\Support\Facades\Http;
use Throwable;

class Helpers
{
    /**
     * Send a Error Report To Telegram Bot.
     *
     * @return static
     */
    public static function SendErrorReportToTelegram(Throwable $exception)
    {
        $type = strtoupper(env('APP_ENV'));
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        Http::post($url, [
            'json' => [
                'chat_id' => (int) env('TELEGRAM_CHAT_ID'),
                'text' => "[{$type}]" .
                "\nFile : " . $exception->getFile() .
                "\nLine : " . $exception->getLine() .
                "\nCode : " . $exception->getCode() .
                "\nMessage : " . $exception->getMessage() .
                "\nTrace : \n" . $exception->getTraceAsString(),
                'disable_notification' => false,
            ],
        ]);
    }
    /**
     * Mapping a Errors Result from Validator.
     *
     * @return static
     */
    public static function MapErrorsValidator(array $errors): array
    {
        $newErrors = [];
        foreach ($errors as $key => $error) {
            array_push($newErrors,
                [
                    'reason' => 'ValidationException',
                    'location_type' => 'body',
                    'location' => $key,
                    'message' => $error[0],
                ]
            );
        }
        return [
            'api_version' => '1.0',
            'error' => [
                'message' => 'cannot be processed.',
                'reason' => 'ValidationException',
                'errors' => $newErrors,
            ],
        ];
    }
    /**
     * Convert Request Status On Body Request.
     *
     * @return static
     */
    public static function ConvertStatusBody(array $body = []): array
    {
        switch (strtolower($body['status'] ?? "")) {
            case 'active':
                $body['status'] = 1;
                break;
            case 'inactive':
                $body['status'] = 0;
                break;
            default:
                $body['status'] = 0;
                break;
        }
        return $body;
    }
}
