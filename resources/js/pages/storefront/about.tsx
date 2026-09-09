import { Head, usePage } from '@inertiajs/react';

export default function StorefrontAbout() {
    const { name: brand } = usePage().props;

    return (
        <>
            <Head title="About" />

            <div className="mx-auto max-w-2xl px-4 py-12 sm:px-6">
                <h1 className="mb-4 text-2xl font-semibold tracking-tight">
                    About
                </h1>
                <div className="text-muted-foreground space-y-4 text-sm leading-relaxed">
                    <p>
                        {brand} is a multi-branch tobacco retailer for
                        cigarettes and related accessories, with pickup at your
                        local store.
                    </p>
                    <p>
                        This page is placeholder copy for layout only and will
                        be replaced with the final Figma design.
                    </p>
                </div>
            </div>
        </>
    );
}
