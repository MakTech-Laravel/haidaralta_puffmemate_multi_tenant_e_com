export type StorefrontCategory = 'Cigarettes' | 'Accessories';

export type StorefrontProduct = {
    id: string;
    name: string;
    price: string;
    category: StorefrontCategory;
};

export const storefrontCategories: StorefrontCategory[] = [
    'Cigarettes',
    'Accessories',
];

export const storefrontProducts: StorefrontProduct[] = [
    {
        id: '1',
        name: 'Classic Menthol Pack',
        price: '$12.50',
        category: 'Cigarettes',
    },
    {
        id: '2',
        name: 'Smooth Gold Pack',
        price: '$11.90',
        category: 'Cigarettes',
    },
    {
        id: '3',
        name: 'Pocket Lighter',
        price: '$4.00',
        category: 'Accessories',
    },
    {
        id: '4',
        name: 'Metal Case',
        price: '$8.50',
        category: 'Accessories',
    },
];
