export interface User {
    id: number;
    name: string;
    email: string;
    role: 'viewer' | 'planner';
    email_verified_at?: string;
}

export interface Dataset {
    version: string;
    source: 'synthetic' | 'real';
    cycle: number;
    priorCycle: number | null;
    refreshedAt: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    dataset: Dataset | null;
};
