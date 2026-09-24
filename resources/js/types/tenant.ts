export type BranchContext = {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
};

export type Portal = 'platform' | 'branch' | 'driver' | 'customer';
