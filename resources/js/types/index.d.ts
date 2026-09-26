export type Role = 'admin' | 'gerencia' | 'jefe_area' | 'marketing';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    area_id: number | null;
    position?: string | null;
    roles: Role[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
