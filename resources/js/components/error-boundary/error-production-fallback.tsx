import { AlertCircle, ArrowLeft, Home, RefreshCw } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export function ErrorProductionFallback() {
    const handleReload = () => window.location.reload();
    const handleBack = () => window.history.back();
    const handleHome = () => (window.location.href = '/');

    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
            <div className="w-full max-w-md space-y-6">
                {/* Status indicator */}
                <div className="flex flex-col items-center gap-1 text-center">
                    <p className="font-mono text-xs font-medium tracking-[0.2em] text-muted-foreground uppercase">
                        Error 500
                    </p>
                </div>

                <Card className="overflow-hidden">
                    {/* Decorative top bar */}
                    <div className="h-1 w-full bg-gradient-to-r from-destructive/60 via-destructive to-destructive/60" />

                    <CardHeader className="pb-4 text-center">
                        <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl border border-destructive/20 bg-destructive/10">
                            <AlertCircle
                                className="size-7 text-destructive"
                                aria-hidden
                            />
                        </div>
                        <CardTitle className="text-xl">
                            Something went wrong
                        </CardTitle>
                        <CardDescription className="text-sm leading-relaxed">
                            An unexpected client-side error occurred while
                            rendering this page.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="pb-4">
                        <Alert
                            variant="destructive"
                            className="border-destructive/30 bg-destructive/5"
                        >
                            <AlertCircle className="size-4" />
                            <AlertTitle className="text-sm font-semibold">
                                We&apos;re sorry for the inconvenience
                            </AlertTitle>
                            <AlertDescription className="mt-1 text-xs leading-relaxed text-destructive/80">
                                Please reload the page to continue. If the
                                problem persists, try clearing your browser
                                cache or contact support.
                            </AlertDescription>
                        </Alert>
                    </CardContent>

                    <CardFooter className="flex flex-col gap-2 pt-2">
                        <Button
                            type="button"
                            className="w-full gap-2"
                            onClick={handleReload}
                        >
                            <RefreshCw className="size-4" aria-hidden />
                            Reload Page
                        </Button>

                        <div className="flex w-full gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1 gap-2"
                                onClick={handleBack}
                            >
                                <ArrowLeft className="size-4" aria-hidden />
                                Go Back
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1 gap-2"
                                onClick={handleHome}
                            >
                                <Home className="size-4" aria-hidden />
                                Home
                            </Button>
                        </div>
                    </CardFooter>
                </Card>

                <p className="text-center text-xs text-muted-foreground">
                    Need help?{' '}
                    <a
                        href="mailto:support@example.com"
                        className="underline underline-offset-4 hover:text-foreground"
                    >
                        Contact support
                    </a>
                </p>
            </div>
        </div>
    );
}
