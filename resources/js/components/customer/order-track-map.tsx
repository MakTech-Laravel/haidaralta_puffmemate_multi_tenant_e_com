import { MapPin } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

export function OrderTrackMap() {
    return (
        <Card className="overflow-hidden py-0 shadow-none">
            <CardContent className="p-0">
                <div className="bg-muted/30 text-muted-foreground flex min-h-56 flex-col items-center justify-center gap-3 px-6 py-10 text-center sm:min-h-72">
                    <MapPin className="size-8 opacity-70" aria-hidden />
                    <div className="space-y-1">
                        <p className="text-foreground text-sm font-medium">
                            Map coming soon
                        </p>
                        <p className="text-xs leading-relaxed">
                            Live delivery tracking will appear here once maps
                            are connected.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
