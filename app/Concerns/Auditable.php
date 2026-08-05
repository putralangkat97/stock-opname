<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn(self $model) => AuditLog::record("created", $model));

        static::updated(
            fn(self $model) => AuditLog::record("updated", $model, [
                "changed" => $model->getChanges(),
            ]),
        );

        static::deleted(fn(self $model) => AuditLog::record("deleted", $model));
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, "auditable")->latest();
    }

    /**
     * For custom, non-CRUD actions — approve(), reject(), start(), complete(), etc.
     * Call this from inside those methods instead of a bare status update, e.g.:
     *
     *   $this->update(['status' => self::STATUS_RECEIVED]);
     *   $this->logAudit('approved');
     */
    public function logAudit(string $action, array $details = []): AuditLog
    {
        return AuditLog::record($action, $this, $details);
    }
}
