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
}

export interface Brand {
    id: number;
    code: string;
    name: string;
}

export interface Unit {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

export interface Warehouse {
    id: number;
    code: string;
    name: string;
}

export interface BinLocation {
    id: number;
    code: string;
    warehouse_id: number;
}

export interface Supplier {
    id: number;
    code: string;
    name: string;
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
    bin_location: BinLocation | null;
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
    received_by: { id: number; name: string };
    items?: GoodsReceiptItem[];
}

export interface Customer {
    id: number;
    code: string;
    name: string;
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
    issued_by: { id: number; name: string };
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
    adjusted_by: { id: number; name: string };
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
    from_warehouse: Warehouse;
    to_warehouse: Warehouse;
    transferred_by: { id: number; name: string };
    received_by: { id: number; name: string } | null;
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
    scanned_by: { id: number; name: string } | null;
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
    assigned_to: { id: number; name: string };
    approved_by: { id: number; name: string } | null;
    items?: StockOpnameItem[];
}
