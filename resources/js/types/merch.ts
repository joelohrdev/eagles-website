import type { SeoMetaForm } from '@/types/site';

export type ProductVariant = {
    id: number;
    product_id: number;
    size: string | null;
    color: string | null;
    sku: string | null;
    stock: number | null;
    price_override: number | null;
    is_active: boolean;
};

export type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    image_path: string | null;
    image_url: string | null;
    image_thumbnail_url: string | null;
    is_active: boolean;
    sort_order: number;
    variants_count?: number;
    variants?: ProductVariant[];
    created_at: string;
    updated_at: string;
};

export type ProductSeo = SeoMetaForm;

export type OrderItem = {
    id: number;
    description: string;
    size: string | null;
    color: string | null;
    unit_price: number;
    quantity: number;
    line_total?: number;
};

export type Order = {
    id: number;
    number: string;
    type: 'merch' | 'camp';
    email: string;
    name: string;
    phone: string | null;
    fulfillment: 'pickup' | 'shipping';
    shipping_address_line1: string | null;
    shipping_address_line2: string | null;
    shipping_city: string | null;
    shipping_state: string | null;
    shipping_postal_code: string | null;
    subtotal: number;
    total: number;
    status: 'pending' | 'paid' | 'fulfilled' | 'cancelled' | 'refunded';
    stripe_checkout_session_id: string | null;
    stripe_payment_intent_id: string | null;
    paid_at: string | null;
    fulfilled_at: string | null;
    notes: string | null;
    created_at: string;
    items_count?: number;
    items?: OrderItem[];
    camp_registration?: {
        id: number;
        player_first_name: string;
        player_last_name: string;
        parent_name: string;
        email: string;
        phone: string;
        status: string;
        camp: {
            id: number;
            name: string;
            slug: string;
            starts_at: string;
            location: string | null;
        };
    } | null;
};

export type CartLine = {
    variant_id: number;
    product_name: string;
    product_slug: string;
    image_thumbnail_url: string | null;
    size: string | null;
    color: string | null;
    label: string;
    stock: number | null;
    quantity: number;
    unit_price: number;
    line_total: number;
};

export type CartData = {
    lines: CartLine[];
    subtotal: number;
    count: number;
};

export type Option = { value: string; label: string };
