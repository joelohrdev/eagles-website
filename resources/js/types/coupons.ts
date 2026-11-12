export type CouponOption = { value: string; label: string };

export type CouponFormOptions = {
    types: CouponOption[];
    scopes: CouponOption[];
    camps: { id: number; name: string }[];
};

export type AdminCouponRow = {
    id: number;
    code: string;
    description: string | null;
    summary: string;
    applies_to_label: string;
    camp_names: string[];
    starts_at: string | null;
    expires_at: string | null;
    max_redemptions: number | null;
    redemptions_count: number;
    is_active: boolean;
};

export type AdminCoupon = {
    id: number;
    code: string;
    description: string | null;
    type: string;
    value: string;
    applies_to: string;
    camp_ids: number[];
    starts_at: string | null;
    expires_at: string | null;
    max_redemptions: number | null;
    redemptions_count: number;
    is_active: boolean;
};
