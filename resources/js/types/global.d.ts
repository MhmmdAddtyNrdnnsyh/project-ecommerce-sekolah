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
                    key: string;
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
                    key: string;
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
                receipt_url?: string;
            };
            [key: string]: unknown;
        };
    }
}
