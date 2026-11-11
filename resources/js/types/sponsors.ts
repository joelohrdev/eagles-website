export type Sponsor = {
    id: number;
    name: string;
    website_url: string | null;
    logo_path: string;
    logo_url: string;
    sort_order: number;
    is_active: boolean;
};

export type HomeSponsor = Pick<
    Sponsor,
    'id' | 'name' | 'website_url' | 'logo_url'
>;
