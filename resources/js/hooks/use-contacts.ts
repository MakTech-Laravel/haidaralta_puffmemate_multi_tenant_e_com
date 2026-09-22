import { usePage } from '@inertiajs/react';

export type ContactSocial = {
    key: string;
    label: string;
    url: string;
    icon?: string | null;
};

export type SharedContacts = {
    phone: string | null;
    fax: string | null;
    email: string | null;
    whatsapp: string | null;
    address: string | null;
    map_embed_url: string | null;
    socials: ContactSocial[];
};

export function useContacts(): SharedContacts {
    return usePage().props.contacts;
}
