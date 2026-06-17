import { ShoppingBag } from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 shrink-0 items-center justify-center rounded-[8px] bg-blue-500 text-white shadow-sm">
                <ShoppingBag className="size-5" fill="currentColor" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-sm leading-tight font-semibold text-inherit">
                    EduCart Admin
                </span>
                <span className="truncate text-xs leading-tight text-current/60">
                    Management Portal
                </span>
            </div>
        </>
    );
}
