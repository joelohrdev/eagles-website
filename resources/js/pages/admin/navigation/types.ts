export type MenuItem = {
    id: number;
    label: string;
    link_type: 'page' | 'custom';
    route_name: string | null;
    url: string | null;
    href: string;
    opens_in_new_tab: boolean;
    is_visible: boolean;
};

export type PageOption = { value: string; label: string };

export type MenuLocation = 'header' | 'footer' | 'footer_bottom';
