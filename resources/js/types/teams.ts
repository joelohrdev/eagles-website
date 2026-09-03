import type { SeoMetaForm } from '@/types';

export type CoachOption = {
    id: number;
    name: string;
};

export type Coach = {
    id: number;
    name: string;
    slug: string;
    title: string | null;
    bio: string | null;
    photo_path: string | null;
    photo_url: string | null;
    photo_thumbnail_url: string | null;
    email: string | null;
    phone: string | null;
    sort_order: number;
    is_active: boolean;
    teams_count?: number;
    created_at: string;
    updated_at: string;
};

export type Team = {
    id: number;
    name: string;
    slug: string;
    division: string;
    season: string | null;
    description: string | null;
    photo_path: string | null;
    photo_url: string | null;
    photo_thumbnail_url: string | null;
    coach_id: number | null;
    coach?: Pick<Coach, 'id' | 'name' | 'title'> | null;
    sort_order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type FacilityPhoto = {
    id: number;
    image_path: string;
    image_url: string;
    thumbnail_url: string;
    caption: string | null;
    sort_order: number;
};

export type SeoMetaProp = (SeoMetaForm & { id: number }) | null;
