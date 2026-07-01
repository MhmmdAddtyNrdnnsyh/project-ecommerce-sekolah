import { Head, Link, usePage } from '@inertiajs/react';
import { Package, ReceiptText, ShoppingCart, Store } from 'lucide-react';
import type { ReactNode } from 'react';
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

type PosProduct = {
    id: number;
    product_name: string;
    available_quantity: number;
};

type DailyReportItem = {
    product_name: string;
    source: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
};

type DailyReportTransaction = {
    id: number;
    code: string;
    receipt_url: string;
    sold_at: string | null;
    total_quantity: number;
    total_amount: number;
    commission_amount: number;
    seller_amount: number;
    products: DailyReportItem[];
};

type Consignment = {
    id: number;
    seller_name: string;
    product_name: string;
    requested_quantity: number;
    received_quantity: number;
    status: { code: string; label: string };
};

type Props = {
    up_jurusan: { id: number; name: string } | null;
    pos_products: PosProduct[];
    consignments: Consignment[];
    daily_report: {
        date: string;
        total_sold: number;
        total_revenue: number;
        submitted_at?: string | null;
        items: DailyReportTransaction[];
    };
};

const transactionChartConfig = {
    total_amount: {
        label: 'Nominal',
        color: '#2563eb',
    },
    total_quantity: {
        label: 'Item',
        color: '#10b981',
    },
} satisfies ChartConfig;

const stockChartConfig = {
    available_quantity: {
        label: 'Stok',
        color: '#f59e0b',
    },
} satisfies ChartConfig;

const sourceChartConfig = {
    revenue: {
        label: 'Nominal',
    },
} satisfies ChartConfig;

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function PicketDashboard({
    up_jurusan,
    pos_products,
    consignments,
    daily_report,
}: Props) {
    const { flash } = usePage().props;
    const lowStock = pos_products.filter(
        (product) => product.available_quantity <= 3,
    ).length;
    const awaitingReceive = consignments.filter(
        (consignment) =>
            consignment.status.code === 'approved' &&
            consignment.received_quantity < consignment.requested_quantity,
    );
    const transactionChartData = daily_report.items.map((item) => ({
        code: item.code,
        total_amount: item.total_amount,
        total_quantity: item.total_quantity,
    }));
    const stockChartData = [...pos_products]
        .sort((a, b) => b.available_quantity - a.available_quantity)
        .slice(0, 6);
    const sourceChartData = Object.values(
        daily_report.items
            .flatMap((transaction) => transaction.products)
            .reduce<
                Record<
                    string,
                    {
                        source: string;
                        label: string;
                        quantity: number;
                        revenue: number;
                        fill: string;
                    }
                >
            >((groups, item) => {
                const key = item.source;

                groups[key] ??= {
                    source: key,
                    label: key,
                    quantity: 0,
                    revenue: 0,
                    fill: key === 'Produk UP' ? '#2563eb' : '#10b981',
                };
                groups[key].quantity += item.quantity;
                groups[key].revenue += item.subtotal;

                return groups;
            }, {}),
    );
    const sourceRevenueTotal = sourceChartData.reduce(
        (total, item) => total + item.revenue,
        0,
    );

    return (
        <>
            <Head title="Dashboard Picket" />
            <main className="min-h-dvh space-y-4 bg-slate-50 p-4 text-slate-950 sm:p-6">
                <section className="rounded-[8px] border border-slate-100 bg-white p-5 shadow-sm">
                    <Badge className="mb-3 rounded-[6px] bg-blue-50 text-blue-700">
                        <Store className="size-3.5" />
                        {up_jurusan?.name ?? 'UP Jurusan'}
                    </Badge>
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Dashboard Picket Officer
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Ringkasan stok dan penjualan hari ini.
                            </p>
                        </div>
                        <Button asChild className="w-fit">
                            <Link href="/picket/pos">
                                <ShoppingCart className="size-4" />
                                Buka POS
                            </Link>
                        </Button>
                    </div>
                </section>

                {(flash.success || flash.error) && (
                    <div
                        role="status"
                        className={`rounded-[8px] border px-4 py-3 text-sm ${
                            flash.error
                                ? 'border-rose-200 bg-rose-50 text-rose-700'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                        }`}
                    >
                        {flash.error || flash.success}
                    </div>
                )}

                <section className="grid gap-3 md:grid-cols-5">
                    <Summary label="Produk aktif" value={pos_products.length} />
                    <Summary
                        label="Menunggu diterima"
                        value={awaitingReceive.length}
                        href="/picket/receiving"
                    />
                    <Summary label="Stok rendah" value={lowStock} />
                    <Summary
                        label="Terjual hari ini"
                        value={daily_report.total_sold}
                    />
                    <Summary
                        label="Omzet"
                        value={formatRupiah(daily_report.total_revenue)}
                    />
                </section>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm lg:col-span-2">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Transaksi POS Hari Ini</CardTitle>
                            <CardDescription>
                                Nominal dan jumlah item per nota yang dicatat
                                picket.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            {transactionChartData.length === 0 ? (
                                <div className="grid h-72 place-items-center text-sm text-slate-500">
                                    Belum ada transaksi POS hari ini.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={transactionChartConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <BarChart
                                        accessibilityLayer
                                        data={transactionChartData}
                                        margin={{
                                            top: 12,
                                            right: 12,
                                            left: -18,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="code"
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
                                            dataKey="total_amount"
                                            fill="var(--color-total_amount)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                        <Bar
                                            dataKey="total_quantity"
                                            fill="var(--color-total_quantity)"
                                            radius={[4, 4, 0, 0]}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Stok POS Tersedia</CardTitle>
                            <CardDescription>
                                Produk dengan stok terbanyak di terminal POS.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            {stockChartData.length === 0 ? (
                                <div className="grid h-72 place-items-center text-sm text-slate-500">
                                    Belum ada produk siap jual.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={stockChartConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <BarChart
                                        accessibilityLayer
                                        data={stockChartData}
                                        layout="vertical"
                                        margin={{
                                            top: 12,
                                            right: 12,
                                            left: 8,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid horizontal={false} />
                                        <XAxis
                                            type="number"
                                            tickLine={false}
                                            axisLine={false}
                                            tickMargin={10}
                                        />
                                        <YAxis
                                            dataKey="product_name"
                                            type="category"
                                            tickLine={false}
                                            axisLine={false}
                                            tickMargin={10}
                                            width={88}
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
                                            dataKey="available_quantity"
                                            fill="var(--color-available_quantity)"
                                            radius={[0, 4, 4, 0]}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 lg:grid-cols-[1fr_1.2fr]">
                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Sumber Penjualan POS</CardTitle>
                            <CardDescription>
                                Proporsi penjualan antara produk UP dan titipan
                                seller hari ini.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col items-center p-5">
                            {sourceRevenueTotal === 0 ? (
                                <div className="grid h-56 place-items-center text-center text-sm text-slate-500">
                                    Belum ada sumber penjualan untuk
                                    divisualkan.
                                </div>
                            ) : (
                                <>
                                    <div className="relative size-56">
                                        <ChartContainer
                                            config={sourceChartConfig}
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
                                                    data={sourceChartData}
                                                    dataKey="revenue"
                                                    nameKey="label"
                                                    innerRadius={58}
                                                    outerRadius={86}
                                                    paddingAngle={2}
                                                    strokeWidth={3}
                                                >
                                                    {sourceChartData.map(
                                                        (entry) => (
                                                            <Cell
                                                                key={
                                                                    entry.source
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
                                        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                                            <span className="text-xs font-medium text-slate-500">
                                                Total
                                            </span>
                                            <span className="text-lg font-semibold text-slate-800 tabular-nums">
                                                {formatRupiah(
                                                    sourceRevenueTotal,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="mt-4 grid w-full gap-3">
                                        {sourceChartData.map((item) => (
                                            <div
                                                key={item.source}
                                                className="rounded-[8px] border border-slate-100 p-3"
                                            >
                                                <div className="flex items-center justify-between gap-3">
                                                    <span className="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-700">
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
                                                    <span className="text-sm font-semibold text-slate-950 tabular-nums">
                                                        {formatRupiah(
                                                            item.revenue,
                                                        )}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-xs text-slate-500 tabular-nums">
                                                    {item.quantity} item terjual
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Ringkasan Setoran Hari Ini</CardTitle>
                            <CardDescription>
                                Breakdown nominal dari transaksi yang akan masuk
                                laporan picket.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 p-5">
                            <MetricRow
                                label="Total omzet POS"
                                value={formatRupiah(daily_report.total_revenue)}
                            />
                            <MetricRow
                                label="Hak seller titipan"
                                value={formatRupiah(
                                    daily_report.items.reduce(
                                        (total, item) =>
                                            total + item.seller_amount,
                                        0,
                                    ),
                                )}
                            />
                            <MetricRow
                                label="Komisi UP Jurusan"
                                value={formatRupiah(
                                    daily_report.items.reduce(
                                        (total, item) =>
                                            total + item.commission_amount,
                                        0,
                                    ),
                                )}
                            />
                            <MetricRow
                                label="Jumlah nota"
                                value={`${daily_report.items.length} transaksi`}
                            />
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 lg:grid-cols-2">
                    <Panel title="Produk perlu dicek" icon={<Package />}>
                        {pos_products.filter(
                            (product) => product.available_quantity <= 3,
                        ).length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Tidak ada stok rendah.
                            </p>
                        ) : (
                            pos_products
                                .filter(
                                    (product) =>
                                        product.available_quantity <= 3,
                                )
                                .map((product) => (
                                    <Row
                                        key={product.id}
                                        label={product.product_name}
                                        value={`Stok ${product.available_quantity}`}
                                    />
                                ))
                        )}
                    </Panel>

                    <Panel title="Order terakhir" icon={<ReceiptText />}>
                        {daily_report.items.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Belum ada penjualan hari ini.
                            </p>
                        ) : (
                            daily_report.items
                                .slice(0, 5)
                                .map((item) => (
                                    <Row
                                        key={item.id}
                                        label={item.code}
                                        value={`${item.total_quantity} item - ${formatRupiah(item.total_amount)}`}
                                        href={item.receipt_url}
                                    />
                                ))
                        )}
                    </Panel>
                </section>
            </main>
        </>
    );
}

function MetricRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-[8px] border border-slate-100 p-3 text-sm">
            <span className="text-slate-500">{label}</span>
            <span className="text-right font-semibold text-slate-950 tabular-nums">
                {value}
            </span>
        </div>
    );
}

function Summary({
    label,
    value,
    href,
}: {
    label: string;
    value: string | number;
    href?: string;
}) {
    const content = (
        <>
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </>
    );

    if (href) {
        return (
            <Link
                href={href}
                className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:bg-blue-50/50"
            >
                {content}
            </Link>
        );
    }

    return (
        <div className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm">
            {content}
        </div>
    );
}

function Panel({
    title,
    icon,
    children,
}: {
    title: string;
    icon: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="rounded-[8px] border border-slate-100 bg-white p-5 shadow-sm">
            <h2 className="mb-4 flex items-center gap-2 font-semibold">
                <span className="text-blue-700 [&_svg]:size-5">{icon}</span>
                {title}
            </h2>
            <div className="space-y-3">{children}</div>
        </section>
    );
}

function Row({
    label,
    value,
    href,
}: {
    label: string;
    value: string;
    href?: string;
}) {
    const className =
        'flex items-center justify-between gap-3 rounded-[8px] border border-slate-100 px-3 py-2 text-sm transition hover:border-blue-200 hover:bg-blue-50/50';
    const content = (
        <>
            <span className="line-clamp-1 font-medium">{label}</span>
            <span className="shrink-0 text-slate-500">{value}</span>
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return <div className={className}>{content}</div>;
}
