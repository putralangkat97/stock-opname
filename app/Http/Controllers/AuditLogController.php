<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BinLocation;
use App\Models\GoodsIssue;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Rack;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Auditable models whose warehouse scope is a direct warehouse_id column.
     * WarehouseTransfer and Warehouse itself are handled separately below,
     * since a transfer has TWO warehouse columns and a Warehouse's own id
     * IS the warehouse — neither fits the direct-column case.
     */
    private const DIRECT_WAREHOUSE_TYPES = [
        Product::class,
        Rack::class,
        BinLocation::class,
        GoodsReceipt::class,
        GoodsIssue::class,
        StockAdjustment::class,
        StockOpname::class,
    ];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $user = Auth::user();
        $query = AuditLog::query()->with('user')->latest();

        // List-level scoping, same pattern as every other index() — the
        // Policy only gates "can this user see the log list at all", not
        // which entries. A Warehouse Admin only sees logs whose auditable
        // record actually belongs to a warehouse they have access to;
        // records with no warehouse concept (User management, Role changes)
        // are Super-Admin-only since Warehouse Admins don't manage those.
        if (! $user->hasRole('Super Admin')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');

            $query->where(function ($outer) use ($warehouseIds) {
                $outer->whereHasMorph(
                    'auditable',
                    self::DIRECT_WAREHOUSE_TYPES,
                    fn ($sub) => $sub->whereIn('warehouse_id', $warehouseIds)
                )->orWhereHasMorph(
                    'auditable',
                    [WarehouseTransfer::class],
                    fn ($sub) => $sub->whereIn('from_warehouse_id', $warehouseIds)
                        ->orWhereIn('to_warehouse_id', $warehouseIds)
                )->orWhereHasMorph(
                    'auditable',
                    [Warehouse::class],
                    fn ($sub) => $sub->whereIn('id', $warehouseIds)
                );
            });
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        return Inertia::render('audit-logs/index', [
            'auditLogs' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['module', 'action']),
            // Distinct value lists for the filter dropdowns — cheap enough
            // to query directly rather than hardcoding, since new modules/
            // actions will show up here automatically as they're logged.
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
