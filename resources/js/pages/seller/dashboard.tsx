import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    ArrowUpRight,
    BadgeDollarSign,
    Boxes,
    Clock3,
    Laptop,
    Megaphone,
    Minus,
    Package,
    PackageCheck,
    ShoppingBag,
    ShoppingCart,
    Store,
    TrendingUp,
    Wallet,
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
import { edit as editProfile } from '@/routes/profile';
import { dashboard as sellerDashboard } from '@/routes/seller';
import { index as sellerInventoryIndex } from '@/routes/seller/inventory';
import { index as sellerOrdersIndex } from '@/routes/seller/orders';
import {
    create as sellerProductsCreate,
    index as sellerProductsIndex,
} from '@/routes/seller/products';

type StatTone = 'blue' | 'emerald' | 'amber' | 'rose';
type OrderStatus = 'pending' | 'packed' | 'sent';

type SellerIconKey =
    | 'alertTriangle'
    | 'badgeDollarSign'
    | 'boxes'
    | 'clock3'
    | 'laptop'
    | 'megaphone'
    | 'package'
    | 'packageCheck'
    | 'shoppingBag'
    | 'shoppingCart'
    | 'store'
    | 'wallet';

type StatCardData = {
    label: string;
    value: string;
    context: string;
    trend: string;
    tone: StatTone;
    icon: SellerIconKey;
};

type SalesPoint = {
    day: string;
    sales: number;
    orders: number;
};

type OrderMixItem = {
    status: OrderStatus;
    label: string;
    value: number;
    fill: string;
};

type OrderItem = {
    id: number;
    order_id: number;
    buyer: string;
    product: string;
    amount: string;
    status: OrderStatus;
    time: string;
};

type TopProductItem = {
    name: string;
    category: string;
    sold: string;
    revenue: string;
    icon: SellerIconKey;
};

type StockAlertItem = {
    product: string;
    sku: string;
    stock: string;
    tone: 'warning' | 'danger';
    icon: SellerIconKey;
};

type TaskItem = {
    title: string;
    detail: string;
    action: string;
    icon: SellerIconKey;
    tone: StatTone;
};

type SellerDashboardProps = {
    dashboard: {
        stats: StatCardData[];
        salesData: SalesPoint[];
        orderMixData: OrderMixItem[];
        orders: OrderItem[];
        topProducts: TopProductItem[];
        stockAlerts: StockAlertItem[];
        tasks: TaskItem[];
    };
};

const iconMap: Record<SellerIconKey, LucideIcon> = {
    alertTriangle: AlertTriangle,
    badgeDollarSign: BadgeDollarSign,
    boxes: Boxes,
    clock3: Clock3,
    laptop: Laptop,
    megaphone: Megaphone,
    package: Package,
    packageCheck: PackageCheck,
    shoppingBag: ShoppingBag,
    shoppingCart: ShoppingCart,
    store: Store,
    wallet: Wallet,
};

const salesConfig = {
    sales: {
        label: 'Omzet',
        color: '#2563eb',
    },
    orders: {
        label: 'Pesanan',
        color: '#10b981',
    },
} satisfies ChartConfig;

const orderMixConfig = {
    value: {
        label: 'Pesanan',
    },
    pending: {
        label: 'Menunggu',
        color: '#2563eb',
    },
    packed: {
        label: 'Dikemas',
        color: '#f59e0b',
    },
    sent: {
        label: 'Dikirim',
        color: '#10b981',
    },
} satisfies ChartConfig;

const toneStyles: Record<
    StatTone,
    {
        icon: string;
        badge: string;
        ring: string;
    }
> = {
    blue: {
        icon: 'bg-blue-50 text-blue-700',
        badge: 'bg-blue-50 text-blue-700',
        ring: 'ring-blue-100',
    },
    emerald: {
        icon: 'bg-emerald-50 text-emerald-700',
        badge: 'bg-emerald-50 text-emerald-700',
        ring: 'ring-emerald-100',
    },
    amber: {
        icon: 'bg-amber-50 text-amber-700',
        badge: 'bg-amber-50 text-amber-700',
        ring: 'ring-amber-100',
    },
    rose: {
        icon: 'bg-rose-50 text-rose-700',
        badge: 'bg-rose-50 text-rose-700',
        ring: 'ring-rose-100',
    },
};

const statusStyles: Record<OrderStatus, string> = {
    pending: 'bg-blue-50 text-blue-700',
    packed: 'bg-amber-50 text-amber-700',
    sent: 'bg-emerald-50 text-emerald-700',
};

const statusLabels: Record<OrderStatus, string> = {
    pending: 'Menunggu',
    packed: 'Dikemas',
    sent: 'Dikirim',
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
                        {stat.tone === 'rose' ? (
                            <Minus className="size-3.5" />
                        ) : (
                            <TrendingUp className="size-3.5" />
                        )}
                        {stat.trend}
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

export default function SellerDashboard({
    dashboard: data,
}: SellerDashboardProps) {
    const orderMixTotal = data.orderMixData.reduce(
        (total, status) => total + status.value,
        0,
    );
    const taskHref = (action: string) => {
        switch (action) {
            case 'Tambah produk':
                return sellerProductsCreate();
            case 'Lihat produk':
                return sellerProductsIndex();
            case 'Proses pesanan':
                return sellerOrdersIndex();
            case 'Lihat profil':
                return editProfile();
            default:
                return sellerDashboard();
        }
    };

    return (
        <>
            <Head title="Dashboard Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                    <Store className="size-3.5" />
                                    Seller Center
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Dashboard Seller
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Pantau performa toko, pesanan, produk, dan stok
                                dalam satu tempat.
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
                                        Performa Penjualan
                                    </CardTitle>
                                    <CardDescription>
                                        Omzet dan jumlah pesanan selama 7 hari
                                    </CardDescription>
                                </div>
                                <CardAction>
                                    <Badge className="rounded-[6px] bg-slate-100 text-slate-600">
                                        Mingguan
                                    </Badge>
                                </CardAction>
                            </CardHeader>
                            <CardContent className="p-6">
                                <ChartContainer
                                    config={salesConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <BarChart
                                        accessibilityLayer
                                        data={data.salesData}
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
                                            dataKey="sales"
                                            fill="var(--color-sales)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                        <Bar
                                            dataKey="orders"
                                            fill="var(--color-orders)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-0">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Status Pesanan
                                </CardTitle>
                                <CardDescription>
                                    Komposisi pesanan aktif toko
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col items-center p-6">
                                <div className="relative size-56">
                                    <ChartContainer
                                        config={orderMixConfig}
                                        className="aspect-square size-full"
                                    >
                                        <PieChart>
                                            <ChartTooltip
                                                cursor={false}
                                                content={
                                                    <ChartTooltipContent
                                                        hideLabel
                                                        nameKey="status"
                                                        className="rounded-[8px] bg-white text-slate-900 ring-slate-200"
                                                    />
                                                }
                                            />
                                            <Pie
                                                data={data.orderMixData}
                                                dataKey="value"
                                                nameKey="label"
                                                innerRadius={58}
                                                outerRadius={86}
                                                paddingAngle={2}
                                                strokeWidth={3}
                                            >
                                                {data.orderMixData.map(
                                                    (entry) => (
                                                        <Cell
                                                            key={entry.status}
                                                            fill={entry.fill}
                                                        />
                                                    ),
                                                )}
                                            </Pie>
                                        </PieChart>
                                    </ChartContainer>
                                    <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                        <span className="text-2xl font-semibold text-slate-800">
                                            {orderMixTotal}
                                        </span>
                                    </div>
                                </div>
                                <div className="mt-4 grid w-full grid-cols-2 gap-2">
                                    {data.orderMixData.map((status) => (
                                        <div
                                            key={status.status}
                                            className="flex min-w-0 items-center gap-2"
                                        >
                                            <span
                                                className="size-3 shrink-0 rounded-full"
                                                style={{
                                                    backgroundColor:
                                                        status.fill,
                                                }}
                                            />
                                            <span className="truncate text-xs font-medium text-slate-600">
                                                {status.label} ({status.value})
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <section className="grid grid-cols-1 gap-6 xl:grid-cols-[1.7fr_1fr]">
                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="flex-row items-center border-b border-slate-100 p-6">
                                <div className="space-y-1">
                                    <CardTitle className="text-xl font-semibold text-slate-950">
                                        Pesanan Terbaru
                                    </CardTitle>
                                    <CardDescription>
                                        Pesanan yang masuk dan perlu ditangani
                                    </CardDescription>
                                </div>
                                <CardAction>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="h-8 rounded-[8px] px-2 text-blue-600 hover:bg-blue-50 hover:text-blue-800"
                                    >
                                        <Link href={sellerOrdersIndex()}>
                                            Semua pesanan
                                            <ArrowUpRight className="size-4" />
                                        </Link>
                                    </Button>
                                </CardAction>
                            </CardHeader>
                            <CardContent className="overflow-x-auto p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="border-slate-100 bg-slate-50 hover:bg-slate-50">
                                            {[
                                                'Order',
                                                'Pembeli',
                                                'Produk',
                                                'Nominal',
                                                'Status',
                                                'Jam',
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
                                        {data.orders.length === 0 && (
                                            <TableRow className="border-slate-100">
                                                <TableCell
                                                    colSpan={6}
                                                    className="px-6 py-8 text-center text-sm text-slate-500"
                                                >
                                                    Belum ada pesanan terbaru.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {data.orders.map((order) => (
                                            <TableRow
                                                key={order.id}
                                                className="border-slate-100 hover:bg-slate-50/70"
                                            >
                                                <TableCell className="px-6 py-4 font-semibold text-slate-950">
                                                    #{order.order_id}
                                                </TableCell>
                                                <TableCell className="px-6 py-4 text-slate-600">
                                                    {order.buyer}
                                                </TableCell>
                                                <TableCell className="px-6 py-4 text-slate-600">
                                                    {order.product}
                                                </TableCell>
                                                <TableCell className="px-6 py-4 font-semibold text-slate-950">
                                                    {order.amount}
                                                </TableCell>
                                                <TableCell className="px-6 py-4">
                                                    <Badge
                                                        variant="secondary"
                                                        className={cn(
                                                            'rounded-full px-2.5 py-0.5',
                                                            statusStyles[
                                                                order.status
                                                            ],
                                                        )}
                                                    >
                                                        {
                                                            statusLabels[
                                                                order.status
                                                            ]
                                                        }
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="px-6 py-4 text-slate-500">
                                                    {order.time}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-4">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Tugas Seller
                                </CardTitle>
                                <CardDescription>
                                    Fokus operasional hari ini
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 p-6 pt-0">
                                {data.tasks.map((task) => {
                                    const Icon = iconMap[task.icon];
                                    const styles = toneStyles[task.tone];

                                    return (
                                        <div
                                            key={task.title}
                                            className="rounded-[8px] border border-slate-100 p-4"
                                        >
                                            <div className="flex items-start gap-3">
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
                                                    <p className="text-sm font-semibold text-slate-950">
                                                        {task.title}
                                                    </p>
                                                    <p className="mt-1 text-sm text-slate-500">
                                                        {task.detail}
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="mt-4 h-8 rounded-[8px] border-slate-200 bg-white px-2 text-xs"
                                            >
                                                <Link
                                                    href={taskHref(task.action)}
                                                >
                                                    {task.action}
                                                </Link>
                                            </Button>
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>
                    </section>

                    <section className="grid grid-cols-1 gap-6 pb-8 lg:grid-cols-2">
                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-4">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Produk Terlaris
                                </CardTitle>
                                <CardDescription>
                                    Produk dengan kontribusi terbesar
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-6 pt-0">
                                {data.topProducts.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Belum ada produk terlaris.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {data.topProducts.map(
                                            (product, index) => {
                                                const Icon =
                                                    iconMap[product.icon];

                                                return (
                                                    <li
                                                        key={product.name}
                                                        className={cn(
                                                            'flex items-center justify-between gap-4',
                                                            index !==
                                                                data.topProducts
                                                                    .length -
                                                                    1 &&
                                                                'border-b border-slate-100 pb-4',
                                                        )}
                                                    >
                                                        <div className="flex min-w-0 items-center gap-4">
                                                            <div className="flex size-12 shrink-0 items-center justify-center rounded-[8px] bg-slate-100 text-slate-500">
                                                                <Icon className="size-5" />
                                                            </div>
                                                            <div className="min-w-0">
                                                                <h3 className="truncate text-sm font-semibold text-slate-950">
                                                                    {
                                                                        product.name
                                                                    }
                                                                </h3>
                                                                <p className="text-xs text-slate-500">
                                                                    {
                                                                        product.category
                                                                    }
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div className="shrink-0 text-right">
                                                            <p className="text-sm font-semibold text-slate-950">
                                                                {product.sold}
                                                            </p>
                                                            <p className="text-xs text-emerald-600">
                                                                {
                                                                    product.revenue
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

                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="p-6 pb-4">
                                <CardTitle className="flex items-center gap-2 text-xl font-semibold text-slate-950">
                                    Stok Rendah
                                    <Badge className="rounded-[6px] bg-rose-50 text-rose-700">
                                        {data.stockAlerts.length} item
                                    </Badge>
                                </CardTitle>
                                <CardDescription>
                                    Produk yang perlu segera diisi ulang
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-6 pt-0">
                                {data.stockAlerts.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Belum ada stok rendah.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {data.stockAlerts.map((item) => {
                                            const Icon = iconMap[item.icon];
                                            const isDanger =
                                                item.tone === 'danger';

                                            return (
                                                <li
                                                    key={item.sku}
                                                    className={cn(
                                                        'flex items-center justify-between gap-4 rounded-[8px] border p-3',
                                                        isDanger
                                                            ? 'border-rose-100 bg-rose-50/70'
                                                            : 'border-amber-100 bg-amber-50/70',
                                                    )}
                                                >
                                                    <div className="flex min-w-0 items-center gap-3">
                                                        <div
                                                            className={cn(
                                                                'flex size-9 shrink-0 items-center justify-center rounded-[6px] bg-white shadow-sm',
                                                                isDanger
                                                                    ? 'text-rose-600'
                                                                    : 'text-amber-600',
                                                            )}
                                                        >
                                                            {isDanger ? (
                                                                <AlertTriangle className="size-4" />
                                                            ) : (
                                                                <Icon className="size-4" />
                                                            )}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <h3 className="truncate text-sm font-semibold text-slate-950">
                                                                {item.product}
                                                            </h3>
                                                            <p className="text-xs text-slate-500">
                                                                SKU: {item.sku}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="shrink-0 text-right">
                                                        <p
                                                            className={cn(
                                                                'text-sm font-bold',
                                                                isDanger
                                                                    ? 'text-rose-600'
                                                                    : 'text-amber-600',
                                                            )}
                                                        >
                                                            {item.stock}
                                                        </p>
                                                        <Button
                                                            asChild
                                                            variant="link"
                                                            className="h-auto rounded-[8px] p-0 text-xs text-blue-600"
                                                        >
                                                            <Link
                                                                href={sellerInventoryIndex()}
                                                            >
                                                                Restock
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                </li>
                                            );
                                        })}
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
    breadcrumbs: [
        {
            title: 'Dashboard Seller',
            href: sellerDashboard(),
        },
    ],
};
