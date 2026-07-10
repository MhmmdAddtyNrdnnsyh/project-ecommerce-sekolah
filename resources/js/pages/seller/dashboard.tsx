import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    ArrowUpRight,
    BadgeDollarSign,
    Boxes,
    ChevronRight,
    Clock3,
    Package,
    ShoppingBag,
    ShoppingCart,
    Store,
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
import { cn } from '@/lib/utils';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { index as sellerInventoryIndex } from '@/routes/seller/inventory';
import { index as sellerOrdersIndex } from '@/routes/seller/orders';
import {
    create as sellerProductsCreate,
    index as sellerProductsIndex,
} from '@/routes/seller/products';

type StatTone = 'blue' | 'emerald' | 'amber' | 'rose';
type OrderStatus =
    'pending' | 'in_production' | 'ready' | 'packed' | 'sent' | 'completed';
type SellerIconKey =
    | 'badgeDollarSign'
    | 'boxes'
    | 'clock3'
    | 'package'
    | 'shoppingBag'
    | 'shoppingCart'
    | 'store';

type SellerDashboardProps = {
    dashboard: {
        stats: {
            label: string;
            value: string;
            context: string;
            tone: StatTone;
            icon: SellerIconKey;
        }[];
        salesData: { day: string; sales: number }[];
        activeOrderData: { key: string; label: string; value: number }[];
        orders: {
            id: number;
            source: 'online' | 'offline';
            code?: string;
            order_id: number | string;
            buyer: string;
            product: string;
            amount: string;
            meta: string | null;
            gross_amount: string | null;
            commission_amount: string | null;
            status: OrderStatus;
            time: string;
        }[];
        topProducts: {
            name: string;
            category: string;
            sold: string;
            revenue: string;
        }[];
        stockAlerts: {
            product: string;
            sku: string;
            stock: string;
            tone: 'warning' | 'danger';
        }[];
        tasks: {
            title: string;
            detail: string;
            action: string;
            icon: SellerIconKey;
            tone: StatTone;
        }[];
    };
};

const iconMap: Record<SellerIconKey, LucideIcon> = {
    badgeDollarSign: BadgeDollarSign,
    boxes: Boxes,
    clock3: Clock3,
    package: Package,
    shoppingBag: ShoppingBag,
    shoppingCart: ShoppingCart,
    store: Store,
};

const toneStyles: Record<StatTone, string> = {
    blue: 'bg-blue-50 text-blue-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    rose: 'bg-rose-50 text-rose-700',
};

const statusStyles: Record<OrderStatus, string> = {
    pending: 'bg-blue-50 text-blue-700',
    in_production: 'bg-violet-50 text-violet-700',
    ready: 'bg-cyan-50 text-cyan-700',
    packed: 'bg-amber-50 text-amber-700',
    sent: 'bg-indigo-50 text-indigo-700',
    completed: 'bg-emerald-50 text-emerald-700',
};

const statusLabels: Record<OrderStatus, string> = {
    pending: 'Menunggu',
    in_production: 'Diproduksi',
    ready: 'Siap',
    packed: 'Dikemas',
    sent: 'Dikirim',
    completed: 'Selesai',
};

const salesConfig = {
    sales: { label: 'Pendapatan seller', color: '#2563eb' },
} satisfies ChartConfig;

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

function StatCard({
    stat,
}: {
    stat: SellerDashboardProps['dashboard']['stats'][number];
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

export default function SellerDashboard({
    dashboard: data,
}: SellerDashboardProps) {
    const hasSales = data.salesData.some((item) => item.sales > 0);
    const taskHref = (action: string) => {
        if (action === 'Tambah produk') {
            return sellerProductsCreate();
        }

        if (action === 'Lihat produk') {
            return sellerProductsIndex();
        }

        return sellerOrdersIndex();
    };

    return (
        <>
            <Head title="Dashboard Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <header>
                        <Badge className="mb-2 rounded-[6px] bg-emerald-50 text-emerald-700">
                            Pusat Seller
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Dashboard Seller
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-slate-500">
                            Lihat pendapatan yang diakui dan selesaikan
                            pekerjaan toko yang masih menunggu.
                        </p>
                    </header>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {data.stats.map((stat) => (
                            <StatCard key={stat.label} stat={stat} />
                        ))}
                    </section>

                    <section>
                        <h2 className="mb-3 text-lg font-semibold text-slate-950">
                            Status Pesanan Aktif
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            {data.activeOrderData.map((item) => (
                                <Link
                                    key={item.key}
                                    href={sellerOrdersIndex()}
                                    className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:bg-blue-50/40"
                                >
                                    <p className="text-sm text-slate-500">
                                        {item.label}
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-slate-950 tabular-nums">
                                        {item.value}
                                    </p>
                                    <span className="mt-3 inline-flex items-center gap-1 text-xs font-medium text-blue-700">
                                        Lihat pesanan
                                        <ChevronRight className="size-3.5" />
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="p-5 pb-4">
                                <CardTitle>Tugas Seller</CardTitle>
                                <CardDescription>
                                    Pekerjaan operasional yang memerlukan
                                    perhatian.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 p-5 pt-0">
                                {data.tasks.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Tidak ada tugas mendesak saat ini.
                                    </p>
                                ) : (
                                    data.tasks.map((task) => {
                                        const Icon = iconMap[task.icon];

                                        return (
                                            <div
                                                key={task.title}
                                                className="flex flex-col items-stretch gap-3 rounded-[8px] border border-slate-100 p-3 sm:flex-row sm:items-center"
                                            >
                                                <div className="flex min-w-0 items-start gap-3 sm:flex-1 sm:items-center">
                                                    <span
                                                        className={`grid size-9 shrink-0 place-items-center rounded-[8px] ${toneStyles[task.tone]}`}
                                                    >
                                                        <Icon className="size-4" />
                                                    </span>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-sm font-semibold text-slate-950">
                                                            {task.title}
                                                        </p>
                                                        <p className="text-xs text-slate-500">
                                                            {task.detail}
                                                        </p>
                                                    </div>
                                                </div>
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                    className="w-full sm:w-auto"
                                                >
                                                    <Link
                                                        href={taskHref(
                                                            task.action,
                                                        )}
                                                    >
                                                        {task.action}
                                                    </Link>
                                                </Button>
                                            </div>
                                        );
                                    })
                                )}
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="p-5 pb-4">
                                <CardTitle>Perhatian Stok</CardTitle>
                                <CardDescription>
                                    Stok habis dan menipis untuk produk siap
                                    jual.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 p-5 pt-0">
                                {data.stockAlerts.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Stok produk aman.
                                    </p>
                                ) : (
                                    data.stockAlerts.map((item) => (
                                        <div
                                            key={item.sku}
                                            className={cn(
                                                'flex items-center justify-between gap-3 rounded-[8px] border p-3',
                                                item.tone === 'danger'
                                                    ? 'border-rose-100 bg-rose-50/70'
                                                    : 'border-amber-100 bg-amber-50/70',
                                            )}
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <AlertTriangle className="size-4 shrink-0 text-rose-600" />
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-semibold">
                                                        {item.product}
                                                    </p>
                                                    <p className="text-xs text-slate-500">
                                                        Stok {item.stock}
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={sellerInventoryIndex()}
                                                >
                                                    Kelola stok
                                                </Link>
                                            </Button>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Pendapatan 7 Hari Terakhir</CardTitle>
                            <CardDescription>
                                Pendapatan dari pesanan online terbayar dan hak
                                seller dari POS.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            {!hasSales ? (
                                <div className="grid h-64 place-items-center text-center text-sm text-slate-500">
                                    Belum ada pendapatan seller dalam tujuh hari
                                    terakhir.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={salesConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <AreaChart
                                        accessibilityLayer
                                        data={data.salesData}
                                        margin={{
                                            left: -10,
                                            right: 12,
                                            top: 12,
                                        }}
                                    >
                                        <defs>
                                            <linearGradient
                                                id="seller-sales"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="5%"
                                                    stopColor="var(--color-sales)"
                                                    stopOpacity={0.3}
                                                />
                                                <stop
                                                    offset="95%"
                                                    stopColor="var(--color-sales)"
                                                    stopOpacity={0.02}
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="day"
                                            tickLine={false}
                                            axisLine={false}
                                            tickMargin={10}
                                        />
                                        <YAxis
                                            tickLine={false}
                                            axisLine={false}
                                            width={76}
                                            tickFormatter={(value) =>
                                                `Rp ${Number(value) / 1000}rb`
                                            }
                                        />
                                        <ChartTooltip
                                            cursor={false}
                                            content={
                                                <ChartTooltipContent
                                                    formatter={(value) => (
                                                        <div className="flex min-w-40 flex-1 items-center justify-between gap-3">
                                                            <span className="text-muted-foreground">
                                                                Pendapatan
                                                                seller
                                                            </span>
                                                            <span className="font-mono font-medium">
                                                                {formatRupiah(
                                                                    Number(
                                                                        value,
                                                                    ),
                                                                )}
                                                            </span>
                                                        </div>
                                                    )}
                                                />
                                            }
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="sales"
                                            stroke="var(--color-sales)"
                                            fill="url(#seller-sales)"
                                            strokeWidth={2}
                                        />
                                    </AreaChart>
                                </ChartContainer>
                            )}
                        </CardContent>
                    </Card>

                    <section className="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-5">
                                <div>
                                    <CardTitle>Transaksi Terbaru</CardTitle>
                                    <CardDescription>
                                        Pesanan online dan penjualan POS
                                        terbaru.
                                    </CardDescription>
                                </div>
                                <Button asChild size="sm" variant="ghost">
                                    <Link href={sellerOrdersIndex()}>
                                        Semua transaksi
                                        <ArrowUpRight className="size-4" />
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent className="overflow-x-auto p-0">
                                <Table className="min-w-[760px]">
                                    <TableHeader>
                                        <TableRow className="bg-slate-50">
                                            <TableHead className="px-5">
                                                Kode
                                            </TableHead>
                                            <TableHead>Produk</TableHead>
                                            <TableHead>Nilai</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="pr-5">
                                                Waktu
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {data.orders.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="py-10 text-center text-sm text-slate-500"
                                                >
                                                    Belum ada transaksi terbaru.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {data.orders.map((order) => (
                                            <TableRow
                                                key={`${order.source}-${order.id}`}
                                            >
                                                <TableCell className="px-5 font-medium">
                                                    <div className="space-y-1">
                                                        <p>
                                                            {order.code ??
                                                                `#${order.order_id}`}
                                                        </p>
                                                        <Badge
                                                            className={
                                                                order.source ===
                                                                'offline'
                                                                    ? 'bg-emerald-50 text-emerald-700'
                                                                    : 'bg-blue-50 text-blue-700'
                                                            }
                                                        >
                                                            {order.source ===
                                                            'offline'
                                                                ? 'POS'
                                                                : 'Online'}
                                                        </Badge>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {order.product}
                                                </TableCell>
                                                <TableCell className="font-semibold">
                                                    {order.amount}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={
                                                            statusStyles[
                                                                order.status
                                                            ]
                                                        }
                                                    >
                                                        {
                                                            statusLabels[
                                                                order.status
                                                            ]
                                                        }
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="pr-5 text-slate-500">
                                                    {order.time}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="p-5 pb-4">
                                <CardTitle>Produk Terlaris Online</CardTitle>
                                <CardDescription>
                                    Pesanan online terbayar, sepanjang waktu.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 pt-0">
                                {data.topProducts.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Belum ada penjualan online terbayar.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {data.topProducts.map(
                                            (product, index) => (
                                                <li
                                                    key={product.name}
                                                    className="flex items-center justify-between gap-3 border-b border-slate-100 pb-4 last:border-0 last:pb-0"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-semibold">
                                                            {index + 1}.{' '}
                                                            {product.name}
                                                        </p>
                                                        <p className="text-xs text-slate-500">
                                                            {product.category}
                                                        </p>
                                                    </div>
                                                    <div className="shrink-0 text-right">
                                                        <p className="text-sm font-semibold">
                                                            {product.sold}
                                                        </p>
                                                        <p className="text-xs text-emerald-600">
                                                            {product.revenue}
                                                        </p>
                                                    </div>
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

SellerDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard Seller', href: sellerDashboard() }],
};
