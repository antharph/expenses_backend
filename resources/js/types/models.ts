export interface Category {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

export interface Store {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Budget {
    id: number;
    user_id: number;
    name: string;
    amount: string;
    reset_type: string;
    reset_days: number[] | string[] | null;
    rollover: boolean;
    created_at: string;
    updated_at: string;
    categories?: Category[];
}

export interface Expense {
    id: number;
    user_id: number;
    category_id: number | null;
    store_id: number | null;
    item: string;
    quantity: number;
    price: string;
    total: string;
    transaction_number: string | null;
    invoice_number: string | null;
    transaction_at: string;
    created_at: string;
    updated_at: string;
    category?: Category;
    store?: Store;
}
