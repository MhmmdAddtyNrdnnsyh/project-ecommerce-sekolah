import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ClipboardCheck,
    PackageCheck,
    Warehouse,
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

type Dashboard = {
    total_up_jurusans: number;
    pending_requests: number;
    approved_requests: number;
    active_stock: number;
    sales_trend_data: {
        day: string;
        reports: number;
        sold: number;
        revenue: number;
    }[];
    status_distribution: {
        status: string;
        label: string;
        value: number;
        fill: string;
    }[];
    stock_distribution: {
        product: string;
        seller: string;
        stock: number;
        sold: number;
    }[];
    recent_requests: {
        id: number;
        seller_name: string;
        product_name: string;
        up_jurusan_name: string;
        requested_quantity: number;
        status: { code: string; label: string };
    }[];
};

type Props = {
    dashboard: Dashboard;
};

const statusChartConfig = {
    value: {
        label: 'Request',
    },
} satisfies ChartConfig;

const salesTrendConfig = {
    reports: {
        label: 'Laporan',
        color: '#2563eb',
    },
    sold: {
        label: 'Item terjual',
        color: '#10b981',
    },
    revenue: {
        label: 'Nilai laporan',
        color: '#f59e0b',
    },
} satisfies ChartConfig;

const stockChartConfig = {
    stock: {
        label: 'Stok aktif',
        color: '#2563eb',
    },
    sold: {
        label: 'Terjual',
        color: '#10b981',
    },
} satisfies ChartConfig;

export default function AdminJurusanDashboard({ dashboard }: Props) {
    const statusTotal = dashboard.status_distribution.reduce(
        (total, item) => total + item.value,
        0,
    );
    const stats = [
        {
            label: 'UP Jurusan',
            value: dashboard.total_up_jurusans,
            icon: Warehouse,
        },
        {
            label: 'Request Pending',
            value: dashboard.pending_requests,
            icon: ClipboardCheck,
        },
        {
            label: 'Request Disetujui',
            value: dashboard.approved_requests,
            icon: ClipboardCheck,
        },
        {
            label: 'Stok Titipan Aktif',
            value: dashboard.active_stock,
            icon: PackageCheck,
        },
    ];

    return (
        <>
            <Head title="Dashboard Admin Jurusan" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    <div className="grid gap-5 p-5 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                Admin Jurusan
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                Dashboard UP Jurusan
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Pantau request titip barang, stok aktif, dan
                                aktivitas terbaru dari satu tempat.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/admin-jurusan/up-jurusan">
                                    UP Jurusan
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/admin-jurusan/consignments">
                                    Review Request
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map(({ label, value, icon: Icon }) => (
                        <div
                            key={label}
                            className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-medium text-slate-500">
                                    {label}
                                </p>
                                <span className="grid size-9 place-items-center rounded-[8px] bg-blue-50 text-blue-700">
                                    <Icon className="size-5" />
                                </span>
                            </div>
                            <p className="mt-4 text-3xl font-semibold text-slate-950 tabular-nums">
                                {value}
                            </p>
                        </div>
                    ))}
                </section>

                <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                    <CardHeader className="p-5 pb-0">
                        <CardTitle>Tren Laporan Picket</CardTitle>
                        <CardDescription>
                            Jumlah laporan, item terjual, dan nilai penjualan
                            dari laporan yang dikirim picket selama 7 hari.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-5">
                        {dashboard.sales_trend_data.every(
                            (item) =>
                                item.reports === 0 &&
                                item.sold === 0 &&
                                item.revenue === 0,
                        ) ? (
                            <div className="grid h-72 place-items-center text-sm text-slate-500">
                                Belum ada laporan picket dalam 7 hari terakhir.
                            </div>
                        ) : (
                            <ChartContainer
                                config={salesTrendConfig}
                                className="aspect-auto h-72 w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={dashboard.sales_trend_data}
                                    margin={{
                                        top: 12,
                                        right: 12,
                                        left: -18,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="day"
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
                                        dataKey="reports"
                                        fill="var(--color-reports)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="sold"
                                        fill="var(--color-sold)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="revenue"
                                        fill="var(--color-revenue)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ChartContainer>
                        )}
                    </CardContent>
                </Card>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Status Request</CardTitle>
                            <CardDescription>
                                Komposisi request titip barang yang masuk.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col items-center p-5">
                            {dashboard.status_distribution.length === 0 ? (
                                <div className="grid h-64 place-items-center text-sm text-slate-500">
                                    Belum ada request untuk divisualkan.
                                </div>
                            ) : (
                                <>
                                    <div className="relative size-56">
                                        <ChartContainer
                                            config={statusChartConfig}
                                            className="aspect-square size-full"
                                        >
                                            <PieChart>
                                                <ChartTooltip
                                                    cursor={false}
                                                    content={
                                                        <ChartTooltipContent
                                                            hideLabel
                                                            nameKey="label"
                                                            className="rounded-[8px] bg-white text-slate-900 ring-slate-200"
                                                        />
                                                    }
                                                />
                                                <Pie
                                                    data={
                                                        dashboard.status_distribution
                                                    }
                                                    dataKey="value"
                                                    nameKey="label"
                                                    innerRadius={58}
                                                    outerRadius={86}
                                                    paddingAngle={2}
                                                    strokeWidth={3}
                                                >
                                                    {dashboard.status_distribution.map(
                                                        (entry) => (
                                                            <Cell
                                                                key={
                                                                    entry.status
                                                                }
                                                                fill={
                                                                    entry.fill
                                                                }
                                                            />
                                                        ),
                                                    )}
                                                </Pie>
                                            </PieChart>
                                        </ChartContainer>
                                        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                            <span className="text-2xl font-semibold text-slate-800 tabular-nums">
                                                {statusTotal}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="mt-4 grid w-full gap-2">
                                        {dashboard.status_distribution.map(
                                            (item) => (
                                                <div
                                                    key={item.status}
                                                    className="flex items-center justify-between gap-3 text-sm"
                                                >
                                                    <span className="flex min-w-0 items-center gap-2 text-slate-600">
                                                        <span
                                                            className="size-3 shrink-0 rounded-full"
                                                            style={{
                                                                backgroundColor:
                                                                    item.fill,
                                                            }}
                                                        />
                                                        <span className="truncate">
                                                            {item.label}
                                                        </span>
                                                    </span>
                                                    <span className="font-semibold text-slate-950 tabular-nums">
                                                        {item.value}
                                                    </span>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm lg:col-span-2">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Stok Titipan per Produk</CardTitle>
                            <CardDescription>
                                Bandingkan stok aktif dan item terjual dari
                                produk titipan paling aktif.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            {dashboard.stock_distribution.length === 0 ? (
                                <div className="grid h-72 place-items-center text-sm text-slate-500">
                                    Belum ada stok atau penjualan titipan.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={stockChartConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <BarChart
                                        accessibilityLayer
                                        data={dashboard.stock_distribution}
                                        margin={{
                                            top: 12,
                                            right: 12,
                                            left: -18,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="product"
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
                                            dataKey="stock"
                                            fill="var(--color-stock)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                        <Bar
                                            dataKey="sold"
                                            fill="var(--color-sold)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between gap-4 border-b border-slate-100 p-4">
                        <div>
                            <h2 className="font-semibold text-slate-950">
                                Request Terbaru
                            </h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Prioritaskan request yang masih menunggu review.
                            </p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/admin-jurusan/consignments">
                                Lihat Semua
                            </Link>
                        </Button>
                    </div>
                    {dashboard.recent_requests.map((item) => (
                        <div
                            key={item.id}
                            className="grid gap-3 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-[1.4fr_1fr_auto] md:items-center"
                        >
                            <div>
                                <p className="font-medium text-slate-950">
                                    {item.product_name}
                                </p>
                                <p className="text-slate-500">
                                    {item.seller_name} - {item.up_jurusan_name}
                                </p>
                            </div>
                            <p className="text-slate-600 tabular-nums">
                                {item.requested_quantity} item
                            </p>
                            <span className="w-fit rounded-[6px] bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-100">
                                {item.status.label}
                            </span>
                        </div>
                    ))}
                    {dashboard.recent_requests.length === 0 && (
                        <div className="p-6 text-sm text-slate-500">
                            Belum ada request titip barang.
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}
