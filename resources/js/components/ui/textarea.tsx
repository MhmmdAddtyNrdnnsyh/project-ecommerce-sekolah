import * as React from 'react';

import { cn } from '@/lib/utils';

function Textarea({
    className,
    ...props
}: React.ComponentProps<'textarea'>) {
    return (
        <textarea
            data-slot="textarea"
            className={cn(
                'min-h-24 w-full min-w-0 rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-base text-slate-950 shadow-none outline-none transition-[color,box-shadow,background-color] placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-3 focus-visible:ring-blue-100 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 md:text-sm',
                className,
            )}
            {...props}
        />
    );
}

export { Textarea };
