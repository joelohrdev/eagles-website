import type { RegistrationState, SeoMetaForm } from '@/types';

export type CampSummary = {
    id: number;
    name: string;
    slug: string;
    location: string | null;
    age_range: string | null;
    starts_at: string;
    ends_at: string | null;
    price: number;
    capacity: number | null;
    image_url: string | null;
    image_thumbnail_url: string | null;
    is_free: boolean;
    registration_state: RegistrationState;
    spots_remaining: number | null;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    url: string;
    register_url: string;
};

export type CampDetail = CampSummary & {
    description: string | null;
    youtube_url: string | null;
};

export type AdminCampRow = {
    id: number;
    name: string;
    slug: string;
    location: string | null;
    starts_at: string;
    ends_at: string | null;
    price: number;
    capacity: number | null;
    is_published: boolean;
    image_thumbnail_url: string | null;
    paid_registrations_count: number;
    active_registrations_count: number;
    registration_state: RegistrationState;
    spots_remaining: number | null;
};

export type AdminCamp = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    location: string | null;
    age_range: string | null;
    starts_at: string;
    ends_at: string | null;
    price: number;
    price_dollars: string;
    capacity: number | null;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    image_url: string | null;
    image_thumbnail_url: string | null;
    youtube_url: string | null;
    is_published: boolean;
    registration_state: RegistrationState;
    public_url: string;
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
    registered_at: string;
    expires_at: string | null;
};

export type CampSeo = SeoMetaForm | null;
