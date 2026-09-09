import { Head } from '@inertiajs/react';

export default function StorefrontContact() {
    return (
        <>
            <Head title="Contact" />

            <div className="mx-auto max-w-2xl px-4 py-12 sm:px-6">
                <h1 className="mb-4 text-2xl font-semibold tracking-tight">
                    Contact
                </h1>
                <dl className="border-border bg-card space-y-4 rounded-lg border p-6 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Branch</dt>
                        <dd className="font-medium">Downtown</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Address</dt>
                        <dd className="font-medium">
                            100 Main Street, Downtown
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Phone</dt>
                        <dd className="font-medium">(555) 010-2000</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Hours</dt>
                        <dd className="font-medium">Mon–Sat 10:00–21:00</dd>
                    </div>
                </dl>
            </div>
        </>
    );
}
