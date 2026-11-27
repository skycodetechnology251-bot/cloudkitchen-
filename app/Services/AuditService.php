<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function log(string $action, ?Model $target = null, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? $target->getMorphClass() : null,
            'target_id' => $target?->getKey(),
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
