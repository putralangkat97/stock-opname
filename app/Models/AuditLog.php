<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        "user_id",
        "role_snapshot",
        "action",
        "module",
        "auditable_type",
        "auditable_id",
        "details",
        "ip_address",
    ];

    protected $casts = [
        "details" => "array",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Single entry point for writing an audit log entry. Call this from the
     * Auditable trait (automatic CRUD events) or directly from a model method
     * for custom actions (approve, reject, start, complete, etc).
     */
    public static function record(
        string $action,
        Model $model,
        array $details = [],
    ): self {
        $user = Auth::user();

        return self::create([
            "user_id" => $user?->id,
            // Requires Spatie's HasRoles trait on User (getRoleNames()).
            "role_snapshot" => $user?->getRoleNames()->first(),
            "action" => $action,
            "module" => class_basename($model),
            "auditable_type" => $model->getMorphClass(),
            "auditable_id" => $model->getKey(),
            "details" => $details,
            "ip_address" => Request::ip(),
        ]);
    }
}
