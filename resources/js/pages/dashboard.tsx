import { Head } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    ArrowUpRight,
    BadgeCheck,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    FileWarning,
    PackageCheck,
    School,
    ShieldCheck,
    Store,
    UserCog,
    UserRoundCheck,
    Users,
    WalletCards,
} from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type StatTone = 'blue' | 'emerald' | 'amber' | 'rose';

type AdminIconKey =
    | 'alertTriangle'
    | 'badgeCheck'
    | 'checkCircle2'
    | 'clipboardCheck'
    | 'clock3'
    | 'fileWarning'
    | 'packageCheck'
    | 'school'
    | 'shieldCheck'
    | 'store'
    | 'userRoundCheck'
    | 'users'
    | 'walletCards';

type StatCardData = {
    label: string;
    value: string;
    context: string;
    tone: StatTone;
    icon: AdminIconKey;
};

type QueuePriority = 'High' | 'Medium' | 'Low';
type QueueStatus = 'Open' | 'In Review' | 'Resolved';

type UserGrowthPoint = {
    month: string;
    users: number;
    sellers: number;
};

type RoleDistributionItem = {
    role: string;
    label: string;
    value: number;
    fill: string;
};

type AdminQueueItem = {
    ticket: string;
    area: string;
    owner: string;
    priority: QueuePriority;
    status: QueueStatus;
    sla: string;
    icon: AdminIconKey;
};

type PlatformHealthItem = {
    label: string;
    value: string;
    progress: number;
    tone: StatTone;
};

type ActivityItem = {
    title: string;
    detail: string;
    time: string;
    icon: AdminIconKey;
    tone: StatTone;
};

type DashboardProps = {
    dashboard: {
        stats: StatCardData[];
        userGrowthData: UserGrowthPoint[];
        roleDistributionData: RoleDistributionItem[];
        adminQueue: AdminQueueItem[];
        platformHealth: PlatformHealthItem[];
        activities: ActivityItem[];
    };
};

const iconMap: Record<AdminIconKey, LucideIcon> = {
    alertTriangle: AlertTriangle,
    badgeCheck: BadgeCheck,
    checkCircle2: CheckCircle2,
    clipboardCheck: ClipboardCheck,
    clock3: Clock3,
    fileWarning: FileWarning,
    packageCheck: PackageCheck,
    school: School,
    shieldCheck: ShieldCheck,
    store: Store,
    userRoundCheck: UserRoundCheck,
    users: Users,
    walletCards: WalletCards,
};

const userGrowthConfig = {
    users: {
        label: 'Pengguna',
        color: '#2563eb',
    },
    sellers: {
        label: 'Seller',
        color: '#10b981',
    },
} satisfies ChartConfig;

const roleDistributionConfig = {
    value: {
        label: 'Persentase',
    },
    buyer: {
        label: 'Buyer',
        color: '#2563eb',
    },
    seller: {
        label: 'Seller',
        color: '#10b981',
    },
    picket: {
        label: 'Petugas Piket',
        color: '#f59e0b',
    },
    admin: {
        label: 'Admin',
        color: '#e11d48',
    },
} satisfies ChartConfig;

const toneStyles: Record<
    StatTone,
    {
        icon: string;
        badge: string;
        bar: string;
        ring: string;
    }
> = {
    blue: {
        icon: 'bg-blue-50 text-blue-700',
        badge: 'bg-blue-50 text-blue-700',
        bar: 'bg-blue-600',
        ring: 'ring-blue-100',
    },
    emerald: {
        icon: 'bg-emerald-50 text-emerald-700',
        badge: 'bg-emerald-50 text-emerald-700',
        bar: 'bg-emerald-600',
        ring: 'ring-emerald-100',
    },
    amber: {
        icon: 'bg-amber-50 text-amber-700',
        badge: 'bg-amber-50 text-amber-700',
        bar: 'bg-amber-500',
        ring: 'ring-amber-100',
    },
    rose: {
        icon: 'bg-rose-50 text-rose-700',
        badge: 'bg-rose-50 text-rose-700',
        bar: 'bg-rose-600',
        ring: 'ring-rose-100',
    },
};

const priorityStyles: Record<QueuePriority, string> = {
    High: 'bg-rose-50 text-rose-700',
    Medium: 'bg-amber-50 text-amber-700',
    Low: 'bg-slate-100 text-slate-600',
};

const statusStyles: Record<QueueStatus, string> = {
    Open: 'bg-rose-50 text-rose-700',
    'In Review': 'bg-blue-50 text-blue-700',
    Resolved: 'bg-emerald-50 text-emerald-700',
};

function StatCard({ stat }: { stat: StatCardData }) {
    const Icon = iconMap[stat.icon];
    const styles = toneStyles[stat.tone];

    return (
        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm transition-shadow hover:shadow-md">
            <CardContent className="p-5">
                <div className="mb-4 flex items-start justify-between gap-3">
                    <div
                        className={cn(
                            'flex size-10 items-center justify-center rounded-[8px]',
                            styles.icon,
                        )}
                    >
                        <Icon className="size-5" />
                    </div>
                    <Badge
                        variant="secondary"
                        className={cn(
                            'h-auto rounded-[6px] px-2 py-1',
                            styles.badge,
                        )}
                    >
                        <ShieldCheck className="size-3.5" />
                        Admin
                    </Badge>
                </div>
                <p className="mb-1 text-sm text-slate-500">{stat.label}</p>
                <p className="text-2xl font-semibold text-slate-950">
                    {stat.value}
                </p>
                <p className="mt-1 text-xs text-slate-500">{stat.context}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ dashboard: data }: DashboardProps) {
    const roleTotal = data.roleDistributionData.reduce(
        (total, role) => total + role.value,
        0,
    );

    return (
        <>
            <Head title="Dashboard Admin" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 flex items-center gap-2">
                                <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                    <UserCog className="size-3.5" />
                                    Admin Console
                                </Badge>
                                <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                    Platform sehat
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Dashboard Admin
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Ringkasan operasional EduCart untuk user,
                                seller, produk, transaksi, dan antrian moderasi.
                            </p>
                        </div>
                    </section>

                    <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {data.stats.map((stat) => (
                            <StatCard key={stat.label} stat={stat} />
                        ))}
                    </section>

                    <section className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm lg:col-span-2">
                            <CardHeader className="flex-row items-start p-6 pb-0">
                                <div className="space-y-1">
                                    <CardTitle className="text-xl font-semibold text-slate-950">
                                        Pertumbuhan Platform
                                    </CardTitle>
                                    <CardDescription>
                                        User baru dan seller aktif per bulan
                                    </CardDescription>
                                </div>
                                <CardAction>
                                    <Badge className="rounded-[6px] bg-slate-100 text-slate-600">
                                        8 bulan
                                    </Badge>
                                </CardAction>
                            </CardHeader>
                            <CardContent className="p-6">
                                <ChartContainer
                                    config={userGrowthConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <BarChart
                                        accessibilityLayer
                                        data={data.userGrowthData}
                                        margin={{
                                            top: 12,
                                            right: 12,
                                            left: -18,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="month"
                                            tickLine={false}
                                            tickMargin={10}
                                            axisLine={false}
                                        />
                                        <YAxis
                                            tickLine={false}
                                            axisLine={false}
                                            tickMargin={10}
                                            width={38}
                                        />
                                        <ChartTooltip
                                            cursor={false}
                                            content={
                                                <ChartTooltipContent
                                                    indicator="dot"
                                                    className="rounded-[8px] bg-white text-slate-900 ring-slate-200"
                                                />
                                            }
                                        />
                                        <Bar
                                            dataKey="users"
                                            fill="var(--color-users)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                        <Bar
                                            dataKey="sellers"
                                            fill="var(--color-sellers)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-0">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Distribusi Role
                                </CardTitle>
                                <CardDescription>
                                    Komposisi akun aktif di platform
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col items-center p-6">
                                <div className="relative size-56">
                                    <ChartContainer
                                        config={roleDistributionConfig}
                                        className="aspect-square size-full"
                                    >
                                        <PieChart>
                                            <ChartTooltip
                                                cursor={false}
                                                content={
                                                    <ChartTooltipContent
                                                        hideLabel
                                                        nameKey="role"
                                                        className="rounded-[8px] bg-white text-slate-900 ring-slate-200"
                                                    />
                                                }
                                            />
                                            <Pie
                                                data={data.roleDistributionData}
                                                dataKey="value"
                                                nameKey="label"
                                                innerRadius={58}
                                                outerRadius={86}
                                                paddingAngle={2}
                                                strokeWidth={3}
                                            >
                                                {data.roleDistributionData.map(
                                                    (entry) => (
                                                        <Cell
                                                            key={entry.role}
                                                            fill={entry.fill}
                                                        />
                                                    ),
                                                )}
                                            </Pie>
                                        </PieChart>
                                    </ChartContainer>
                                    <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                        <span className="text-2xl font-semibold text-slate-800">
                                            {roleTotal}%
                                        </span>
                                    </div>
                                </div>
                                <div className="mt-4 grid w-full grid-cols-2 gap-2">
                                    {data.roleDistributionData.map((role) => (
                                        <div
                                            key={role.role}
                                            className="flex min-w-0 items-center gap-2"
                                        >
                                            <span
                                                className="size-3 shrink-0 rounded-full"
                                                style={{
                                                    backgroundColor: role.fill,
                                                }}
                                            />
                                            <span className="truncate text-xs font-medium text-slate-600">
                                                {role.label} ({role.value}%)
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="flex-row items-center border-b border-slate-100 p-6">
                            <div className="space-y-1">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Antrian Tindakan Admin
                                </CardTitle>
                                <CardDescription>
                                    Tiket yang perlu ditinjau tim operasional
                                </CardDescription>
                            </div>
                            <CardAction>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="h-8 rounded-[8px] px-2 text-blue-600 hover:bg-blue-50 hover:text-blue-800"
                                >
                                    Semua tiket
                                    <ArrowUpRight className="size-4" />
                                </Button>
                            </CardAction>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow className="border-slate-100 bg-slate-50 hover:bg-slate-50">
                                        {[
                                            'Tiket',
                                            'Area',
                                            'Pemilik',
                                            'Prioritas',
                                            'Status',
                                            'SLA',
                                            '',
                                        ].map((heading) => (
                                            <TableHead
                                                key={heading}
                                                className="h-11 px-6 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                            >
                                                {heading}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.adminQueue.length === 0 && (
                                        <TableRow className="border-slate-100">
                                            <TableCell
                                                colSpan={7}
                                                className="px-6 py-8 text-center text-sm text-slate-500"
                                            >
                                                Tidak ada antrian tindakan admin
                                                saat ini.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {data.adminQueue.map((item) => {
                                        const Icon = iconMap[item.icon];

                                        return (
                                            <TableRow
                                                key={item.ticket}
                                                className="border-slate-100 hover:bg-slate-50/70"
                                            >
                                                <TableCell className="px-6 py-4 font-semibold text-slate-950">
                                                    {item.ticket}
                                                </TableCell>
                                                <TableCell className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex size-8 items-center justify-center rounded-[6px] bg-slate-100 text-slate-600">
                                                            <Icon className="size-4" />
                                                        </div>
                                                        <span className="font-medium text-slate-700">
                                                            {item.area}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="px-6 py-4 text-slate-600">
                                                    {item.owner}
                                                </TableCell>
                                                <TableCell className="px-6 py-4">
                                                    <Badge
                                                        variant="secondary"
                                                        className={cn(
                                                            'rounded-full px-2.5 py-0.5',
                                                            priorityStyles[
                                                                item.priority
                                                            ],
                                                        )}
                                                    >
                                                        {item.priority}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="px-6 py-4">
                                                    <Badge
                                                        variant="secondary"
                                                        className={cn(
                                                            'rounded-full px-2.5 py-0.5',
                                                            statusStyles[
                                                                item.status
                                                            ],
                                                        )}
                                                    >
                                                        {item.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="px-6 py-4 text-slate-500">
                                                    {item.sla}
                                                </TableCell>
                                                <TableCell className="px-6 py-4 text-right">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="h-8 rounded-[8px] border-slate-200 bg-white px-2"
                                                    >
                                                        Tinjau
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <section className="grid grid-cols-1 gap-6 pb-8 lg:grid-cols-2">
                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-4">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Kesehatan Platform
                                </CardTitle>
                                <CardDescription>
                                    Indikator operasional yang dipantau admin
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-5 p-6 pt-0">
                                {data.platformHealth.map((item) => {
                                    const styles = toneStyles[item.tone];

                                    return (
                                        <div
                                            key={item.label}
                                            className="space-y-2"
                                        >
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="text-sm font-medium text-slate-700">
                                                    {item.label}
                                                </span>
                                                <span className="text-sm font-semibold text-slate-950">
                                                    {item.value}
                                                </span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div
                                                    className={cn(
                                                        'h-full rounded-full',
                                                        styles.bar,
                                                    )}
                                                    style={{
                                                        width: `${item.progress}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-4">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Aktivitas Terbaru
                                </CardTitle>
                                <CardDescription>
                                    Audit singkat dari tindakan admin
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-6 pt-0">
                                {data.activities.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Belum ada aktivitas terbaru.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {data.activities.map(
                                            (activity, index) => {
                                                const Icon =
                                                    iconMap[activity.icon];
                                                const styles =
                                                    toneStyles[activity.tone];

                                                return (
                                                    <li
                                                        key={`${activity.title}-${activity.time}`}
                                                        className={cn(
                                                            'flex items-start gap-3',
                                                            index !==
                                                                data.activities
                                                                    .length -
                                                                    1 &&
                                                                'border-b border-slate-100 pb-4',
                                                        )}
                                                    >
                                                        <div
                                                            className={cn(
                                                                'flex size-9 shrink-0 items-center justify-center rounded-[8px] ring-4',
                                                                styles.icon,
                                                                styles.ring,
                                                            )}
                                                        >
                                                            <Icon className="size-4" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex items-start justify-between gap-3">
                                                                <p className="text-sm font-semibold text-slate-950">
                                                                    {
                                                                        activity.title
                                                                    }
                                                                </p>
                                                                <span className="shrink-0 text-xs text-slate-400">
                                                                    {
                                                                        activity.time
                                                                    }
                                                                </span>
                                                            </div>
                                                            <p className="mt-1 text-sm text-slate-500">
                                                                {
                                                                    activity.detail
                                                                }
                                                            </p>
                                                        </div>
                                                    </li>
                                                );
                                            },
                                        )}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </main>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
