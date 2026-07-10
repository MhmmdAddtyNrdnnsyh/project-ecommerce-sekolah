import AppLogoIcon from '@/components/app-logo-icon';

type Props = {
    title?: string;
    subtitle?: string | null;
};

export default function AppLogo({
    title = 'EduCart',
    subtitle = 'Portal Sekolah',
}: Props) {
    return (
        <>
            <div className="flex aspect-square size-10 shrink-0 items-center justify-center rounded-[8px] bg-transparent">
                <AppLogoIcon className="size-8 object-contain" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-sm leading-tight font-semibold text-inherit">
                    {title}
                </span>
                {subtitle && (
                    <span className="truncate text-xs leading-tight text-current/60">
                        {subtitle}
                    </span>
                )}
            </div>
        </>
    );
}
