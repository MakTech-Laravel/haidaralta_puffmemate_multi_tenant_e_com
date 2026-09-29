import {
    formatStorefrontAud,
    type StorefrontPackDetail,
} from '@/types/storefront';

type PackSpecCardsProps = {
    pack: StorefrontPackDetail;
    className?: string;
};

type SpecCard = {
    label: string;
    value: string;
    description: string;
};

function offerWindow(pack: StorefrontPackDetail): SpecCard {
    if (pack.endsAt) {
        return {
            label: 'Offer window',
            value: `Until ${pack.endsAt}`,
            description: pack.startsAt
                ? `Started ${pack.startsAt}`
                : 'Limited-time pack price',
        };
    }

    return {
        label: 'Offer window',
        value: 'Always on',
        description: 'Available while pack stock lasts',
    };
}

export function PackSpecCards({ pack, className }: PackSpecCardsProps) {
    const cards: SpecCard[] = [
        {
            label: 'Products in pack',
            value: `${pack.productCount} ${pack.productCount === 1 ? 'product' : 'products'}`,
            description: 'Hand-picked branch stock bundled together',
        },
        {
            label: 'Total units',
            value: `${pack.totalUnits} ${pack.totalUnits === 1 ? 'unit' : 'units'}`,
            description: 'Every item packed in one courier drop',
        },
        {
            label: 'You save',
            value: pack.savings ? formatStorefrontAud(pack.savings) : '—',
            description:
                pack.discountPercent > 0
                    ? `${pack.discountPercent}% off buying separately`
                    : 'Bundle pack price',
        },
        offerWindow(pack),
    ];

    return (
        <section className={className}>
            <p className="text-primary text-[11px] font-bold tracking-wider uppercase">
                Pack details
            </p>
            <h2 className="text-foreground mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">
                Verified Pack Specifications
            </h2>
            <div className="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
                {cards.map((card) => (
                    <div key={card.label} className="bg-muted/60 rounded-xl p-4">
                        <p className="text-muted-foreground text-[10px] font-bold tracking-wider uppercase">
                            {card.label}
                        </p>
                        <p className="text-foreground mt-2 text-lg leading-tight font-bold">
                            {card.value}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs">
                            {card.description}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}
