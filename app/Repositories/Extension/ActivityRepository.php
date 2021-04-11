<?php
namespace App\Repositories\Extension;

use App\Models\Extension\Activity;

trait ActivityRepository
{
    public function AddActivity(?array $body = []): array
    {
        if (!is_object(Activity::create($body))) {
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
