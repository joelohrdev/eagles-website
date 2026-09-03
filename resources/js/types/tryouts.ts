import type { RegistrationState, SeoMetaForm } from '@/types';

export type Tryout = {
    id: number;
    title: string;
    slug: string;
    division: string;
    location: string | null;
    description: string | null;
    event_at: string;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    capacity: number | null;
    image_path?: string | null;
    image_url: string | null;
    image_thumbnail_url: string | null;
    is_published?: boolean;
    registration_state: RegistrationState;
    spots_remaining: number | null;
    registrations_count?: number;
    url?: string;
    register_url?: string;
    created_at?: string;
    updated_at?: string;
};

export type TryoutRegistration = {
    id: number;
    tryout_id: number;
    player_first_name: string;
    player_last_name: string;
    player_birthdate: string;
    parent_name: string;
    email: string;
    phone: string;
    current_team: string | null;
    primary_position: string | null;
    notes: string | null;
    registered_at: string;
};

export type TryoutSeo = SeoMetaForm | null;
