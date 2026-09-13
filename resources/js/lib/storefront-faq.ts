import type { LucideIcon } from 'lucide-react';
import {
    MapPinned,
    PackageCheck,
    ShieldCheck,
    Truck,
    Wrench,
} from 'lucide-react';

export const faqHero = {
    badge: 'Help & Knowledge Desk / 12 Hubs Connected',
    title: 'How can we help you today?',
    subtitle:
        'Find clear answers regarding our branch-aware ordering, live dispatch tracking, discreet packaging, verification, and customer support.',
};

export type FaqItem = {
    id: string;
    question: string;
    answer: string;
    defaultOpen?: boolean;
};

export type FaqCategory = {
    id: string;
    title: string;
    icon: LucideIcon;
    items: FaqItem[];
};

export const faqCategories: FaqCategory[] = [
    {
        id: 'multi-branch',
        title: 'Multi-Branch & Local Inventory',
        icon: MapPinned,
        items: [
            {
                id: 'branch-aware',
                question: 'How does branch-aware ordering work?',
                answer:
                    'When you choose a delivery location, we show stock and pricing from nearby branches that can fulfil your cart. You pick a branch before checkout so availability is reserved from that store.',
            },
            {
                id: 'price-availability',
                question:
                    'Can prices or product availability differ between branches?',
                answer:
                    'Yes. Each branch manages its own inventory and pricing. What you see in cart and checkout always reflects the branch you selected for that order.',
            },
            {
                id: 'change-branch',
                question: 'Can I change my branch after placing an order?',
                answer:
                    'Once an order is accepted by a branch, the fulfilment location is locked. Cancel and reorder from another branch if you need a different store — subject to stock and cut-off times.',
            },
        ],
    },
    {
        id: 'delivery',
        title: 'Delivery & Real-Time Tracking',
        icon: Truck,
        items: [
            {
                id: 'live-tracking',
                question: 'How does live order tracking work?',
                answer:
                    'From the moment your local branch accepts the order, you can follow its full lifecycle: Processing → Preparing → Driver Assigned → On The Way → Arrived. Once the courier departs, you receive a real-time GPS telemetry map showing the courier\'s route and exact minute ETA.',
                defaultOpen: true,
            },
            {
                id: 'stages-timeframes',
                question:
                    'What are the typical delivery stages and timeframes?',
                answer:
                    'Same-day local deliveries usually move from acceptance to out-for-delivery within hours, depending on your slot. Australia-wide parcels typically arrive in 2–5 business days after dispatch.',
            },
            {
                id: 'courier-contact',
                question:
                    'Can I communicate directly with my courier during transit?',
                answer:
                    'When your order is out for delivery, in-app updates and support channels share status and ETA. Direct courier chat is available on eligible local runs where the driver has messaging enabled.',
            },
        ],
    },
    {
        id: 'privacy',
        title: 'Privacy, Discretion & Packaging',
        icon: PackageCheck,
        items: [
            {
                id: 'discreet-packaging',
                question: 'Is packaging completely discreet?',
                answer:
                    'All customer shipments arrive in plain, unmarked matte luxury outer cartons with no external logos, brand references, or product indicators. Deliveries are indistinguishable from standard courier parcels.',
                defaultOpen: true,
            },
            {
                id: 'tamper-evident',
                question: 'Are products sealed with tamper-evident tape?',
                answer:
                    'Yes. Inner packs use tamper-evident seals where required. If a seal looks broken on arrival, refuse the handover and contact support for a DOA review.',
            },
        ],
    },
    {
        id: 'security',
        title: 'Security & OTP Handover',
        icon: ShieldCheck,
        items: [
            {
                id: 'otp-handover',
                question: 'Why is OTP verification required at handover?',
                answer:
                    'A one-time code confirms you (or your authorised adult recipient) are present. It protects age-restricted goods and reduces misdelivery or third-party interception.',
            },
            {
                id: 'age-docs',
                question: 'What age verification documents are accepted?',
                answer:
                    'Valid government photo ID such as a driver licence, passport, or proof-of-age card. The recipient must be 18+; orders may be refused if ID cannot be verified.',
            },
        ],
    },
    {
        id: 'warranty',
        title: 'Warranty, Returns & Replacements',
        icon: Wrench,
        items: [
            {
                id: 'doa-policy',
                question:
                    'What is your Dead-on-Arrival (DOA) replacement policy?',
                answer:
                    'Report DOA issues within 24 hours of delivery with photos of packaging and the product. Eligible DOA items are replaced from the fulfilling branch or refunded when stock cannot be sourced.',
            },
        ],
    },
];
