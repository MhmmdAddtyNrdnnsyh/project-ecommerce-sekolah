import { Link, usePage } from '@inertiajs/react';
import type { CSSProperties } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

type AuthTheme = CSSProperties & Record<`--${string}`, string>;

const lightAuthTheme: AuthTheme = {
    '--background': '#F8FAFC',
    '--foreground': '#0F172A',
    '--card': '#FFFFFF',
    '--card-foreground': '#0F172A',
    '--popover': '#FFFFFF',
    '--popover-foreground': '#0F172A',
    '--primary': '#0080FF',
    '--primary-foreground': '#FFFFFF',
    '--secondary': '#F1F5F9',
    '--secondary-foreground': '#334155',
    '--muted': '#F1F5F9',
    '--muted-foreground': '#64748B',
    '--accent': '#EFF8FF',
    '--accent-foreground': '#0059B8',
    '--border': '#E2E8F0',
    '--input': '#E2E8F0',
    '--ring': '#BCE0FF',
    '--destructive': '#DC2626',
    '--destructive-foreground': '#FFFFFF',
};

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { component } = usePage();
    const isLightAuthPage =
        component === 'auth/login' || component === 'auth/register';

    return (
        <div
            className={cn(
                'flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10',
                isLightAuthPage
                    ? 'bg-[#F8FAFC] text-[#0F172A]'
                    : 'bg-background',
            )}
            style={isLightAuthPage ? lightAuthTheme : undefined}
        >
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon
                                    className={cn(
                                        'size-9 fill-current',
                                        isLightAuthPage
                                            ? 'text-[#0080FF]'
                                            : 'text-[var(--foreground)]',
                                    )}
                                />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1
                                className={cn(
                                    'text-xl font-medium',
                                    isLightAuthPage && 'text-[#0F172A]',
                                )}
                            >
                                {title}
                            </h1>
                            <p
                                className={cn(
                                    'text-center text-sm',
                                    isLightAuthPage
                                        ? 'text-[#64748B]'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
