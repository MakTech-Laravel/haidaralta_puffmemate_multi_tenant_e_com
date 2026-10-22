import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type PageProps = {
    builtAt: string;
    appEnv: string;
    phpVersion: string;
    pingCount: number;
    hint: string;
};

/**
 * TEMP — Coolify BuildKit cache smoke test page. Delete after verifying.
 */
export default function DeployCacheTest({
    builtAt,
    appEnv,
    phpVersion,
    pingCount,
    hint,
}: PageProps) {
    return (
        <>
            <Head title="TEMP Deploy Cache Test" />

            <main className="mx-auto flex min-h-svh max-w-lg flex-col justify-center gap-6 px-4 py-10">
                <div className="space-y-2">
                    <p className="text-muted-foreground text-xs tracking-wide uppercase">
                        Temporary — remove after test
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Deploy cache test
                    </h1>
                    <p className="text-muted-foreground text-sm">{hint}</p>
                </div>

                <dl className="border-border divide-border divide-y rounded-md border text-sm">
                    <div className="flex justify-between gap-4 px-3 py-2">
                        <dt className="text-muted-foreground">Rendered at</dt>
                        <dd className="font-mono text-xs">{builtAt}</dd>
                    </div>
                    <div className="flex justify-between gap-4 px-3 py-2">
                        <dt className="text-muted-foreground">APP_ENV</dt>
                        <dd className="font-mono text-xs">{appEnv}</dd>
                    </div>
                    <div className="flex justify-between gap-4 px-3 py-2">
                        <dt className="text-muted-foreground">PHP</dt>
                        <dd className="font-mono text-xs">{phpVersion}</dd>
                    </div>
                    <div className="flex justify-between gap-4 px-3 py-2">
                        <dt className="text-muted-foreground">Session pings</dt>
                        <dd className="font-mono text-xs">{pingCount}</dd>
                    </div>
                </dl>

                <Form
                    action="/__temp/deploy-cache-test/ping"
                    method="post"
                    className="flex flex-wrap gap-3"
                >
                    <Button type="submit">Ping (session +1)</Button>
                    <Button variant="outline" type="button" asChild>
                        <a href="/">Back home</a>
                    </Button>
                </Form>
            </main>
        </>
    );
}
