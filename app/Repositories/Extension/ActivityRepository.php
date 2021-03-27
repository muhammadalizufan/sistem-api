<?php
namespace App\Repositories\Extension;

use App\Models\Extension\Activity;

trait ActivityRepository
{
    public function AddActivity(?array $body = []): array
    {
        // Check Body
        if (count($body) <= 0) {
            return [
                "status" => false,
                "message" => "failed add user activity, body is empty",
            ];
        }
        $A = Activity::create([
            'user_id' => $body['user_id'],
            'ref_type' => $body['ref_type'],
            'ref_id' => $body['ref_id'],
            'action' => $body['action'],
            'message_id' => $body['message_id'],
            'message_en' => $body['message_en'],
        ]);
        if (!is_object($A)) {
            return [
                "status" => false,
                "message" => "failed add user activity",
            ];
        }
        return [
            "status" => true,
        ];
    }
}
