import type { LucideIcon } from 'lucide-react';
import {
    CheckCircle2,
    MapPin,
    Package,
    ShoppingBag,
    ShoppingCart,
    Store,
    Truck,
    UserRound,
    Wallet,
} from 'lucide-react';

export const aboutHero = {
    title: 'About Us',
    subtitle:
        'Fresh product, fast service — always there for customers across Australia.',
    image: '/images/storefront/hero-1.jpg',
};

export const aboutWhoWeAre = {
    eyebrow: 'Who we are',
    heading: 'More Than An Online Store',
    body: 'PuffMeMate connects you with trusted local branches for cigarettes, tobacco, vapes and more — with instant delivery, clear stock, and a simple checkout.',
    mediaImage: '/images/storefront/about.jpg',
    mediaTitle: 'Connected Platform',
    mediaCaption: 'Download App Now. Shopping, Branches, Delivery.',

    features: [
        {
            title: 'Online Shopping',
            description:
                'Browse categories and products from your phone or desktop in minutes.',
        },
        {
            title: 'Smooth Store Ordering',
            description:
                'Pick a nearby branch, reserve stock, and check out without friction.',
        },
        {
            title: 'Multi-Branch Stock',
            description:
                'See what is available where you are — the right branch, the right inventory.',
        },
        {
            title: 'Fast Local Delivery',
            description:
                'Same-day local drops and Australia-wide shipping when you need it.',
        },
    ],
};

export type AboutStep = {
    number: string;
    title: string;
    description: string;
    icon: LucideIcon;
};

export const aboutHowItWorks = {
    eyebrow: 'How it works',
    heading: 'From Selection To Delivery — All In One Experience',
    body: 'A clear path from choosing your branch to watching your order arrive — built for speed and certainty.',
    steps: [
        {
            number: '01',
            title: 'Choose Your Location',
            description: 'Set where you want products delivered or collected.',
            icon: MapPin,
        },
        {
            number: '02',
            title: 'Select Branch',
            description: 'Pick the nearest branch with the stock you need.',
            icon: Store,
        },
        {
            number: '03',
            title: 'Browse Products',
            description: 'Explore cigarettes, tobacco, vapes and accessories.',
            icon: ShoppingBag,
        },
        {
            number: '04',
            title: 'Add To Cart',
            description: 'Build your order and reserve availability instantly.',
            icon: ShoppingCart,
        },
        {
            number: '05',
            title: 'Checkout',
            description:
                'Confirm details, delivery method and payment at door.',
            icon: Wallet,
        },
        {
            number: '06',
            title: 'Order Processing',
            description:
                'Your branch prepares the order carefully and quickly.',
            icon: Package,
        },
        {
            number: '07',
            title: 'Driver Assigned',
            description: 'A local driver is matched for your delivery window.',
            icon: UserRound,
        },
        {
            number: '08',
            title: 'Live Delivery Tracking',
            description: 'Follow status updates from dispatch to doorstep.',
            icon: Truck,
        },
        {
            number: '09',
            title: 'Order Delivered',
            description:
                'Receive your order with discreet packaging and ID check.',
            icon: CheckCircle2,
        },
    ] satisfies AboutStep[],
};

export const aboutMultiBranch = {
    eyebrow: 'Multi branch inventory',
    heading: 'Your Order. The Right Branch. The Right Stock.',
    body: 'We weight distance, delivery location and live inventory so every order routes to a branch that can fulfil it — not a generic warehouse promise.',
    promise: 'Product availability and pricing can vary by branch.',
    factors: [
        {
            number: '01',
            title: 'Distance',
            description: 'Prefer branches closest to your delivery pin.',
        },
        {
            number: '02',
            title: 'Delivery Location',
            description: 'Match coverage zones for same-day local drops.',
        },
        {
            number: '03',
            title: 'Live Stock',
            description: 'Only show branches that can fulfil your cart.',
        },
        {
            number: '04',
            title: 'Customer Preference',
            description: 'Remember your favourite branch when it fits.',
        },
        {
            number: '05',
            title: 'Fulfilment Speed',
            description: 'Prioritise branches ready to pick and dispatch.',
        },
    ],
};

export type AboutTrackingStatus = {
    label: string;
    state: 'done' | 'active' | 'pending';
};

export const aboutDelivery = {
    eyebrow: 'Delivery experience',
    heading: 'Stay Connected From Order To Doorstep',
    paragraphs: [
        'Track every stage after checkout — from processing and preparation through driver assignment and out-for-delivery.',
        'Clear status updates keep you informed without chasing support, so you know exactly when your order is on the way.',
    ],
    statuses: [
        { label: 'Processing', state: 'done' },
        { label: 'Preparing', state: 'done' },
        { label: 'Driver Assigned', state: 'done' },
        { label: 'Out For Delivery', state: 'active' },
        { label: 'Arrived', state: 'pending' },
        { label: 'Delivered', state: 'pending' },
    ] satisfies AboutTrackingStatus[],
};

export const aboutCta = {
    eyebrow: 'Start shopping',
    heading: 'Ready For Your Next Order?',
    body: 'Browse the catalogue, pick your branch, and get fresh product delivered with the same care as in-store.',
    primaryLabel: 'Shop Now',
    secondaryLabel: 'Our Products',
};
