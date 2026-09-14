import { Head, Link, usePage } from '@inertiajs/react';
import {
    CreditCard,
    Heart,
    Package,
    ShoppingBag,
    Sparkles,
    UserRound,
} from 'lucide-react';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Separator } from '@/components/ui/separator';
import type { Auth } from '@/types';

const orders = [
    {
        id: '#1842',
        placed: 'Today, 9:12 AM',
        total: '$42.80',
        status: 'Ready for pickup',
        items: 'Rose 12-pack, mint sleeves',
    },
    {
        id: '#1790',
        placed: 'Tue · Sydney CBD',
        total: '$18.40',
        status: 'Delivered',
        items: 'Classic tin',
    },
    {
        id: '#1744',
        placed: 'Last week',
        total: '$61.00',
        status: 'Delivered',
        items: 'Gift bundle',
    },
];

const recs = [
    { name: 'Rose 12-pack', price: '$24.00', tag: 'Popular' },
    { name: 'Mint sleeves', price: '$9.50', tag: 'Restock' },
    { name: 'Harbor sampler', price: '$16.00', tag: 'New' },
    { name: 'Weekend bundle', price: '$32.00', tag: 'Save 10%' },
];

const shortcuts = [
    { title: 'Orders', href: '/activity', icon: Package },
    { title: 'Profile', href: '/settings/profile', icon: UserRound },
    { title: 'Saved', href: '/dashboard', icon: Heart },
    { title: 'Wallet', href: '/settings/profile', icon: CreditCard },
];

export default function CustomerDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;
    const firstName = user?.name.split(/\s+/u)[0] ?? 'there';

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-8 p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Welcome, {user?.name}
                        </h1>
                        <p className="text-muted-foreground max-w-xl text-base">
                            Hi {firstName} — pickup, recent orders, and a few
                            things you might like.
                        </p>
                    </div>
                    <DemoDataBadge />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card className="bg-accent gap-4 py-6 shadow-none">
                        <CardHeader className="px-6">
                            <Badge variant="secondary" className="w-fit">
                                <Sparkles className="size-3" />
                                This weekend
                            </Badge>
                            <CardTitle className="text-xl">
                                Free pickup over $25
                            </CardTitle>
                            <CardDescription>
                                Placeholder promo for Sydney CBD. No code
                                required on demo orders.
                            </CardDescription>
                        </CardHeader>
                        <CardFooter className="px-6">
                            <Button type="button">Shop the offer</Button>
                        </CardFooter>
                    </Card>
                    <Card className="gap-4 py-6 shadow-none">
                        <CardHeader className="px-6">
                            <Badge variant="outline" className="w-fit">
                                Ready today
                            </Badge>
                            <CardTitle className="text-xl">
                                Order #1842 is packed
                            </CardTitle>
                            <CardDescription>
                                Pickup counter · Sydney CBD · show this screen
                                at the desk.
                            </CardDescription>
                        </CardHeader>
                        <CardFooter className="px-6">
                            <Button type="button" variant="outline">
                                <ShoppingBag data-icon="inline-start" />
                                View pickup details
                            </Button>
                        </CardFooter>
                    </Card>
                </div>

                <section className="flex flex-col gap-4">
                    <div className="flex items-end justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Recent orders
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Placeholder history for this account.
                            </p>
                        </div>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/activity" prefetch>
                                My activity
                            </Link>
                        </Button>
                    </div>
                    <div className="grid gap-3">
                        {orders.map((order) => (
                            <Card
                                key={order.id}
                                className="gap-3 py-4 shadow-none"
                            >
                                <CardHeader className="flex flex-row items-start justify-between gap-3 px-5">
                                    <div>
                                        <CardTitle className="text-base">
                                            {order.id}
                                        </CardTitle>
                                        <CardDescription>
                                            {order.items}
                                        </CardDescription>
                                    </div>
                                    <Badge variant="secondary">
                                        {order.status}
                                    </Badge>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex justify-between px-5 text-sm">
                                    <span>{order.placed}</span>
                                    <span className="text-foreground font-medium tabular-nums">
                                        {order.total}
                                    </span>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </section>

                <section className="flex flex-col gap-4">
                    <div>
                        <h2 className="text-lg font-semibold">
                            Recommended for you
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Mock merchandising tiles — not live catalog data.
                        </p>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {recs.map((item) => (
                            <Card
                                key={item.name}
                                className="gap-3 overflow-hidden py-0 shadow-none"
                            >
                                <div className="bg-muted relative h-28">
                                    <PlaceholderPattern className="stroke-muted-foreground/20 absolute inset-0 size-full" />
                                </div>
                                <CardHeader className="px-4 pt-4 pb-0">
                                    <Badge
                                        variant="outline"
                                        className="w-fit font-normal"
                                    >
                                        {item.tag}
                                    </Badge>
                                    <CardTitle className="text-base">
                                        {item.name}
                                    </CardTitle>
                                </CardHeader>
                                <CardFooter className="justify-between px-4 pt-0 pb-4">
                                    <span className="text-sm font-medium tabular-nums">
                                        {item.price}
                                    </span>
                                    <Button type="button" size="sm">
                                        Add
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                </section>

                <Separator />

                <section className="flex flex-col gap-4">
                    <h2 className="text-lg font-semibold">Account shortcuts</h2>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {shortcuts.map((item) => (
                            <Button
                                key={item.title}
                                variant="outline"
                                className="h-auto justify-start gap-3 px-4 py-3"
                                asChild
                            >
                                <Link href={item.href} prefetch>
                                    <item.icon className="size-4" />
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

CustomerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ],
};
