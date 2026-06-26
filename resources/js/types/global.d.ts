import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            adminHeader: {
                notifications: {
                    type: 'product';
                    title: string;
                    description: string;
                    href: string;
                }[];
                supportEmail: string | null;
            } | null;
            buyerHeader: {
                cartItemsCount: number;
            } | null;
            sellerHeader: {
                notifications: {
                    type: 'order' | 'stock';
                    title: string;
                    description: string;
                    href: string;
                }[];
                supportEmail: string | null;
            } | null;
            sidebarOpen: boolean;
            flash: {
                success?: string;
                error?: string;
            };
            [key: string]: unknown;
        };
    }
}
