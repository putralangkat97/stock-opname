export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Category {
    id: number;
    code: string;
    name: string;
    description: string | null;
    products_count?: number;
}

export interface Brand {
    id: number;
    code: string;
    name: string;
    description: string | null;
    logo_url: string | null;
    products_count?: number;
}

export interface Unit {
    id: number;
    code: string;
    name: string;
    symbol: string;
    products_count?: number;
}

export interface Warehouse {
    id: number;
    code: string;
    name: string;
    location: string | null;
    manager: string | null;
    phone: string | null;
    total_capacity: number;
}

export interface BinLocation {
    id: number;
    code: string;
    warehouse_id: number;
    rack_id?: number;
    capacity?: number;
}

// Eloquent relation names keep their exact PHP method-name casing in JSON
// (unlike DB columns, which are naturally snake_case) — Rack::binLocations()
// serializes as "binLocations", not "bin_locations".
export interface Rack {
    id: number;
    warehouse_id: number;
    code: string;
    zone: string | null;
    binLocations?: BinLocation[];
}

// Shape returned by WarehouseController::show() — warehouse with racks eager
// loaded, each rack with its binLocations eager loaded.
export interface WarehouseWithRacks extends Warehouse {
    racks: Rack[];
}

export interface Supplier {
    id: number;
    code: string;
    name: string;
    contact_person: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
}

export interface Product {
    id: number;
    sku: string;
    barcode: string | null;
    name: string;
    stock: number;
    min_stock: number;
    max_stock: number | null;
    cost_price: string;
    selling_price: string;
    status: "In Stock" | "Low Stock" | "Out of Stock";
    category: Category;
    brand: Brand;
    unit: Unit;
    warehouse: Warehouse;
    binLocation: BinLocation | null;
}

export interface GoodsReceiptItem {
    id: number;
    product_id: number;
    product_sku_snapshot: string;
    product_name_snapshot: string;
    qty: number;
    unit_price: string;
    subtotal: string;
    product?: Product;
}

export type GoodsReceiptStatusValue = "Draft" | "Received" | "Cancelled";

export interface GoodsReceipt {
    id: number;
    receipt_number: string;
    po_number: string | null;
    date: string;
    status: GoodsReceiptStatusValue;
    total_amount: string;
    notes: string | null;
    supplier: Supplier;
    warehouse: Warehouse;
    receivedBy: { id: number; name: string };
    items?: GoodsReceiptItem[];
}

export interface Customer {
    id: number;
    code: string;
    name: string;
    contact_person: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
}

export interface GoodsIssueItem {
    id: number;
    product_id: number;
    product_sku_snapshot: string;
    product_name_snapshot: string;
    qty: number;
    unit_price: string;
    subtotal: string;
    product?: Product;
}

export type GoodsIssueStatusValue = "Draft" | "Issued" | "Cancelled";

export interface GoodsIssue {
    id: number;
    issue_number: string;
    so_number: string | null;
    date: string;
    status: GoodsIssueStatusValue;
    total_amount: string;
    notes: string | null;
    customer: Customer;
    warehouse: Warehouse;
    issuedBy: { id: number; name: string };
    items?: GoodsIssueItem[];
}

export type StockAdjustmentTypeValue = "IN" | "OUT";
export type StockAdjustmentReasonValue =
    "Damaged" | "Expired" | "Lost" | "Found" | "Correction";
export type StockAdjustmentStatusValue = "Pending" | "Approved" | "Rejected";

export interface StockAdjustmentItem {
    id: number;
    product_id: number;
    product_sku_snapshot: string;
    product_name_snapshot: string;
    qty: number;
    product?: Product;
}

export interface StockAdjustment {
    id: number;
    adjustment_number: string;
    type: StockAdjustmentTypeValue;
    reason: StockAdjustmentReasonValue;
    date: string;
    status: StockAdjustmentStatusValue;
    notes: string | null;
    warehouse: Warehouse;
    adjustedBy: { id: number; name: string };
    items?: StockAdjustmentItem[];
}

export type WarehouseTransferStatusValue =
    "Pending" | "In Transit" | "Completed" | "Rejected";

export interface WarehouseTransferItem {
    id: number;
    product_id: number;
    product_sku_snapshot: string;
    product_name_snapshot: string;
    qty: number;
    product?: Product;
}

export interface WarehouseTransfer {
    id: number;
    transfer_number: string;
    date: string;
    status: WarehouseTransferStatusValue;
    notes: string | null;
    fromWarehouse: Warehouse;
    toWarehouse: Warehouse;
    transferredBy: { id: number; name: string };
    receivedBy: { id: number; name: string } | null;
    items?: WarehouseTransferItem[];
}

export type StockOpnameStatusValue =
    "Draft" | "In Progress" | "Completed" | "Approved" | "Rejected";
export type StockOpnameItemStatusValue =
    "Matched" | "Surplus" | "Shortage" | "Uncounted";

export interface StockOpnameItem {
    id: number;
    product_id: number;
    product_sku_snapshot: string;
    product_name_snapshot: string;
    system_qty: number;
    physical_qty: number | null;
    scanned_at: string | null;
    notes: string | null;
    status: StockOpnameItemStatusValue;
    scannedBy: { id: number; name: string } | null;
    product?: Product;
}

export interface StockOpname {
    id: number;
    opname_number: string;
    title: string;
    start_date: string;
    completed_date: string | null;
    status: StockOpnameStatusValue;
    total_system_qty: number;
    total_physical_qty: number;
    total_variance_qty: number;
    total_variance_value: string;
    notes: string | null;
    approved_at: string | null;
    warehouse: Warehouse;
    assignedTo: { id: number; name: string };
    approvedBy: { id: number; name: string } | null;
    items?: StockOpnameItem[];
}
