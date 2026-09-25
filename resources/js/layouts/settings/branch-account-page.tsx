import type { ReactNode } from 'react';
import Heading from '@/components/heading';

export default function BranchAccountPage({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex w-full flex-col gap-4 p-2 md:p-4">
            <Heading title={title} description={description} />
            {children}
        </div>
    );
}
