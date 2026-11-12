export type ActivityEntry = {
    id: number;
    event: string;
    description: string;
    actor: string;
    /** True when a signed-in staff member did it. */
    is_staff: boolean;
    properties: Record<string, unknown>;
    ip_address: string | null;
    created_at: string;
    link: { label: string; url: string } | null;
};
