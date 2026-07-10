import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowUpRight,
    PackageCheck,
    Store,
    Users,
    WalletCards,
} from 'lucide-react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
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
import { dashboard } from '@/routes';

type StatTone = 'blue' | 'emerald' | 'amber' | 'rose';
type AdminIconKey = 'users' | 'store' | 'packageCheck' | 'walletCards';

type DashboardProps = {
    dashboard: {
        stats: {
            label: string;
            value: string;
            context: string;
            tone: StatTone;
            icon: AdminIconKey;
        }[];
        orderTrendData: { month: string; orders: number; revenue: number }[];
        adminQueue: {
            key: string;
            type: string;
            title: string;
            owner: string;
            status: string;
            age: string;
            href: string;
        }[];
        activities: {
            title: string;
            detail: string;
            time: string;
        }[];
    };
};

const iconMap: Record<AdminIconKey, LucideIcon> = {
    users: Users,
    store: Store,
    packageCheck: PackageCheck,
    walletCards: WalletCards,
};

const toneStyles: Record<StatTone, string> = {
    blue: 'bg-blue-50 text-blue-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    rose: 'bg-rose-50 text-rose-700',
};

const orderTrendConfig = {
    orders: { label: 'Order online', color: '#2563eb' },
} satisfies ChartConfig;

const formatNumber = (value: number) =>
    new Intl.NumberFormat('id-ID').format(value);

function StatCard({
    stat,
}: {
    stat: DashboardProps['dashboard']['stats'][number];
}) {
    const Icon = iconMap[stat.icon];

    return (
        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-sm text-slate-500">{stat.label}</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-950 tabular-nums">
                            {stat.value}
                        </p>
                    </div>
                    <span
                        className={`grid size-10 shrink-0 place-items-center rounded-[8px] ${toneStyles[stat.tone]}`}
                    >
                        <Icon className="size-5" />
                    </span>
                </div>
                <p className="mt-2 text-xs text-slate-500">{stat.context}</p>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ dashboard: data }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard Admin" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <header>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            Administrasi EduCart
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Dashboard Admin
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-slate-500">
                            Tinjau moderasi, pengajuan seller, dan aktivitas
                            order online.
                        </p>
                    </header>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {data.stats.map((stat) => (
                            <StatCard key={stat.label} stat={stat} />
                        ))}
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="flex-col items-stretch gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle>Antrian Tindakan Admin</CardTitle>
                                <CardDescription>
                                    Moderasi dan pengajuan yang menunggu
                                    tinjauan.
                                </CardDescription>
                            </div>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button asChild size="sm" variant="outline">
                                    <Link href="/admin/products/moderation">
                                        Moderasi Produk
                                    </Link>
                                </Button>
                                <Button asChild size="sm" variant="outline">
                                    <Link href="/admin/seller-applications">
                                        Pengajuan Seller
                                    </Link>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="overflow-x-auto p-0">
                            <Table className="min-w-[760px]">
                                <TableHeader>
                                    <TableRow className="bg-slate-50">
                                        <TableHead className="px-5">
                                            Jenis
                                        </TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Pemilik</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Umur</TableHead>
                                        <TableHead className="pr-5 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.adminQueue.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="py-10 text-center text-sm text-slate-500"
                                            >
                                                Tidak ada tindakan admin yang
                                                menunggu.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {data.adminQueue.map((item) => (
                                        <TableRow key={item.key}>
                                            <TableCell className="px-5 font-medium">
                                                {item.type}
                                            </TableCell>
                                            <TableCell>{item.title}</TableCell>
                                            <TableCell>{item.owner}</TableCell>
                                            <TableCell>
                                                <Badge className="rounded-[6px] bg-amber-50 text-amber-700">
                                                    {item.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-slate-500">
                                                {item.age}
                                            </TableCell>
                                            <TableCell className="pr-5 text-right">
                                                <Button asChild size="sm">
                                                    <Link href={item.href}>
                                                        Tinjau
                                                        <ArrowUpRight className="size-4" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <section className="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="p-5 pb-0">
                                <CardTitle>Aktivitas Order Online</CardTitle>
                                <CardDescription>
                                    Jumlah order online selama delapan bulan
                                    terakhir. Admin hanya memantau status order
                                    dan pembayaran.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5">
                                <ChartContainer
                                    config={orderTrendConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <AreaChart
                                        accessibilityLayer
                                        data={data.orderTrendData}
                                        margin={{
                                            left: -18,
                                            right: 12,
                                            top: 12,
                                        }}
                                    >
                                        <defs>
                                            <linearGradient
                                                id="admin-orders"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="5%"
                                                    stopColor="var(--color-orders)"
                                                    stopOpacity={0.3}
                                                />
                                                <stop
                                                    offset="95%"
                                                    stopColor="var(--color-orders)"
                                                    stopOpacity={0.02}
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="month"
                                            tickLine={false}
                                            axisLine={false}
                                            tickMargin={10}
                                        />
                                        <YAxis
                                            allowDecimals={false}
                                            tickLine={false}
                                            axisLine={false}
                                            width={38}
                                        />
                                        <ChartTooltip
                                            cursor={false}
                                            content={
                                                <ChartTooltipContent
                                                    formatter={(value) => (
                                                        <span className="font-mono font-medium">
                                                            {formatNumber(
                                                                Number(value),
                                                            )}{' '}
                                                            order
                                                        </span>
                                                    )}
                                                />
                                            }
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="orders"
                                            stroke="var(--color-orders)"
                                            fill="url(#admin-orders)"
                                            strokeWidth={2}
                                        />
                                    </AreaChart>
                                </ChartContainer>
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="p-5 pb-4">
                                <CardTitle>Pengguna Baru</CardTitle>
                                <CardDescription>
                                    Akun yang baru terdaftar di EduCart.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 pt-0">
                                {data.activities.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Belum ada pengguna baru.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {data.activities.map(
                                            (activity, index) => (
                                                <li
                                                    key={`${activity.detail}-${index}`}
                                                    className="border-b border-slate-100 pb-4 last:border-0 last:pb-0"
                                                >
                                                    <div className="flex justify-between gap-3">
                                                        <p className="text-sm font-semibold text-slate-950">
                                                            {activity.title}
                                                        </p>
                                                        <span className="shrink-0 text-xs text-slate-400">
                                                            {activity.time}
                                                        </span>
                                                    </div>
                                                    <p className="mt-1 text-sm text-slate-500">
                                                        {activity.detail}
                                                    </p>
                                                </li>
                                            ),
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
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
