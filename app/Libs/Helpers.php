<?php
namespace App\Libs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    /**
     * Iteration Child Array Permission.
     *
     * @return static
     */
    public static function IterationPermissionChild(?Collection $Array, ?array $child)
    {
        foreach ($child as $key => $c) {
            $Array->push($c['value']);
            if (is_array($c['child'] ?? false)) {
                self::IterationPermissionChild($Array, $c['child']);
            }
        }
    }
    /**
     * Map User Payload.
     *
     * @return static
     */
    public static function MapUserPayload(?array $U = null): ?array
    {
        if (count($U) > 0) {
            if ($U['role']) {
                $U['role'] = [
                    'id' => $U['role']['role']['id'] ?? "",
                    'name' => $U['role']['role']['name'] ?? "",
                ];
            }
            if ($U['group']) {
                $U['group'] = [
                    'id' => $U['group']['group']['id'] ?? "",
                    'name' => $U['group']['group']['name'] ?? "",
                ];
            }
            unset($U['access_token'], $U['password'], $U['pin'], $U['use_twofa']);
        }
        return $U ?? null;
    }
    /**
     * Remove Extension From String.
     *
     * @return static
     */
    public static function StrWithoutExtension(string $string = "")
    {
        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $string);
    }
    /**
     * Convert Dateline Body.
     *
     * @return static
     */
    public static function ConvertDatelineBodyToDate(Request $r)
    {
        switch ($r->input('dateline', 'ThreeDay')) {
            case 'OneDay':
                $r->merge(['dateline' => Carbon::now()]);
                break;
            case 'TwoDay':
                $r->merge(['dateline' => Carbon::now()->addDays(2)]);
                break;
            case 'ThreeDay':
                $r->merge(['dateline' => Carbon::now()->addDays(3)]);
                break;
        }
    }
    /**
     * Generate Random String.
     *
     * @return static
     */
    public static function QuickRandom($length = 16): string
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }
    /**
     * Remove Script Tag In HTML String.
     *
     * @return static
     */
    public static function RMScriptTagHTML(?string $html = null, bool $onlyBody = false): string
    {
        if (is_null($html)) {
            return "";
        }
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $dom->normalizeDocument();
        $script = $dom->getElementsByTagName('script');
        $remove = [];
        foreach ($script as $item) {
            $remove[] = $item;
        }
        foreach ($remove as $item) {
            $item->parentNode->removeChild($item);
        };
        if (!$onlyBody) {
            $html = $dom->saveHTML();
            return is_string($html) ? $html : "";
        } else {
            return self::GetContentHTML($dom);
        }
    }
    /**
     * Returns the normalized content.
     *
     * @since  3.0.0
     *
     * @return static HTML content
     */
    public static function GetContentHTML(\DOMDocument $dom): string
    {
        $body = $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
        return str_replace(array('<body>', '</body>'), '', $body);
    }
}
