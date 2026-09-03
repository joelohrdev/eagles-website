export type SiteInfo = {
    org_name: string;
    phone: string | null;
    email: string | null;
    address_line1: string | null;
    address_city: string | null;
    address_state: string | null;
    address_postal_code: string | null;
    social_facebook: string | null;
    social_instagram: string | null;
    social_twitter: string | null;
    social_youtube: string | null;
    social_tiktok: string | null;
};

export type SeoProps = {
    title: string;
    description: string | null;
    canonical_url: string;
    robots: string;
    site_name: string;
    share_title: string;
    share_description: string | null;
    share_image_url: string | null;
    share_image_alt: string | null;
    share_image_width: number | null;
    share_image_height: number | null;
    twitter_card: string;
    twitter_handle: string | null;
    facebook_app_id: string | null;
    og_type: string;
    json_ld: Record<string, unknown>[];
};

/** Editable SEO & Sharing fields sent with admin forms. */
export type SeoMetaForm = {
    title: string | null;
    description: string | null;
    canonical_url: string | null;
    robots: string | null;
    share_title: string | null;
    share_description: string | null;
    share_image_path: string | null;
    share_image_url: string | null;
    share_image_alt: string | null;
    twitter_card: string | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type RegistrationState = 'open' | 'upcoming' | 'closed' | 'full';

export type NavigationLink = {
    id: number | null;
    label: string;
    href: string;
    external: boolean;
    new_tab: boolean;
};

export type NavigationSettings = {
    nav_cta_label: string | null;
    nav_cta_url: string | null;
    nav_show_cta: boolean;
    nav_show_cart: boolean;
    footer_tagline: string | null;
    footer_links_heading: string | null;
    footer_contact_heading: string | null;
    footer_show_contact: boolean;
    footer_show_socials: boolean;
    footer_show_address: boolean;
    footer_copyright: string | null;
};

export type NavigationProps = {
    menus: {
        header: NavigationLink[];
        footer: NavigationLink[];
        footer_bottom: NavigationLink[];
    };
    settings: NavigationSettings;
};
