import type { RegistrationState, SeoMetaForm } from '@/types';

export type CampSummary = {
    id: number;
    name: string;
    slug: string;
    location: string | null;
    address: string | null;
    address_details: string | null;
    map_url: string | null;
    age_range: string | null;
    starts_at: string | null;
    ends_at: string | null;
    price: number;
    capacity: number | null;
    image_url: string | null;
    image_thumbnail_url: string | null;
    is_free: boolean;
    has_pricing_options: boolean;
    registration_state: RegistrationState;
    spots_remaining: number | null;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    url: string;
    register_url: string;
};

export type CampScheduleDay = {
    id: number;
    date: string | null;
    title: string;
    label: string | null;
    times: { age_group_id: number | null; range: string }[];
    remaining: { age_group_id: number | null; remaining: number | null }[];
};

export type CampScheduleSession = {
    id: number;
    name: string;
    description: string | null;
    price: number | null;
    day_price: number | null;
    days: CampScheduleDay[];
};

export type CampDetail = CampSummary & {
    description: string | null;
    youtube_url: string | null;
    coaches: CampCoach[];
    bundle_price: number | null;
    age_groups: { id: number; name: string }[];
    sessions: CampScheduleSession[];
};

export type CampQuote = {
    lines: {
        session_id: number;
        name: string;
        description: string;
        day_ids: number[];
        whole_session: boolean;
        price: number;
    }[];
    subtotal: number;
    discounts: { label: string; amount: number }[];
    discount: number;
    discount_label: string | null;
    coupon_code: string | null;
    coupon_note: string | null;
    total: number;
};

export type AdminCampRow = {
    id: number;
    name: string;
    slug: string;
    location: string | null;
    starts_at: string | null;
    ends_at: string | null;
    price: number;
    capacity: number | null;
    is_published: boolean;
    image_thumbnail_url: string | null;
    paid_registrations_count: number;
    active_registrations_count: number;
    registration_state: RegistrationState | 'draft';
    spots_remaining: number | null;
    archived_at: string | null;
    can_delete: boolean;
};

export type CampScheduleForm = {
    age_groups: {
        key: string;
        id: number | null;
        name: string;
        capacity: number | null;
    }[];
    sessions: {
        id: number | null;
        name: string;
        description: string | null;
        price: string | null;
        day_price: string | null;
        days: {
            id: number | null;
            date: string | null;
            label: string | null;
            times: {
                age_group_key: string | null;
                starts_at: string | null;
                ends_at: string | null;
            }[];
        }[];
    }[];
};

export type AdminCamp = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    location: string | null;
    address: string | null;
    address_details: string | null;
    age_range: string | null;
    starts_at: string | null;
    ends_at: string | null;
    price: number;
    bundle_price_dollars: string | null;
    schedule: CampScheduleForm;
    coaches: CampCoach[];
    capacity: number | null;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    image_url: string | null;
    image_thumbnail_url: string | null;
    youtube_url: string | null;
    is_published: boolean;
    registration_state: RegistrationState;
    public_url: string;
    preview_url: string;
    preview_passcode: string | null;
    archived_at: string | null;
    can_delete: boolean;
};

export type AdminCampRegistration = {
    id: number;
    player_first_name: string;
    player_last_name: string;
    player_name: string;
    player_birthdate: string;
    parent_name: string;
    email: string;
    phone: string;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    medical_notes: string | null;
    status: string;
    status_label: string;
    order_number: string | null;
    order_id: number | null;
    age_group: string | null;
    schedule: string[];
    discount_label: string | null;
    discount: number;
    registered_at: string;
    expires_at: string | null;
};

export type CampSeo = SeoMetaForm | null;

/** Links for the staff-only preview pages (they also work for drafts). */
export type CampPreview = {
    is_draft: boolean;
    /** Only for signed-in staff; passcode visitors can't edit. */
    edit_url: string | null;
    camp_url: string;
    register_url: string;
};

/** A coach on one camp's page (separate from the main coaching staff). */
export type CampCoach = {
    id: number;
    name: string;
    title: string | null;
    bio: string | null;
    photo_url: string | null;
};
