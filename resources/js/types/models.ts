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