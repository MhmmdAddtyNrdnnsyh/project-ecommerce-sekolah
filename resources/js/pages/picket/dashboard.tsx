import { Head, Link, usePage } from '@inertiajs/react';
import { Package, ReceiptText, ShoppingCart, Store } from 'lucide-react';
import type { ReactNode } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
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
    sold_quantity: number;
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

const movementChartConfig = {
    received_quantity: {
        label: 'Barang masuk',
        color: '#2563eb',
    },
    sold_quantity: {
        label: 'Barang keluar',
        color: '#10b981',
    },
} satisfies ChartConfig;

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatNumber = (value: number) =>
    new Intl.NumberFormat('id-ID').format(value);

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
    const movementChartData = [...consignments]
        .filter(
            (consignment) =>
                consignment.received_quantity > 0 ||
                consignment.sold_quantity > 0,
        )
        .sort(
            (a, b) =>
                b.received_quantity +
                b.sold_quantity -
                (a.received_quantity + a.sold_quantity),
        )
        .slice(0, 6);

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

                <section>
                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="p-5 pb-0">
                            <CardTitle>Barang Masuk dan Keluar</CardTitle>
                            <CardDescription>
                                Perbandingan barang diterima dan barang terjual
                                per produk titipan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            {movementChartData.length === 0 ? (
                                <div className="grid h-72 place-items-center text-sm text-slate-500">
                                    Belum ada barang masuk atau keluar.
                                </div>
                            ) : (
                                <ChartContainer
                                    config={movementChartConfig}
                                    className="aspect-auto h-72 w-full"
                                >
                                    <BarChart
                                        accessibilityLayer
                                        data={movementChartData}
                                        barCategoryGap="26%"
                                        margin={{
                                            top: 12,
                                            right: 12,
                                            left: -18,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="product_name"
                                            tickLine={false}
                                            tickMargin={10}
                                            axisLine={false}
                                        />
                                        <YAxis
                                            tickLine={false}
                                            axisLine={false}
                                            tickMargin={10}
                                            width={38}
                                            allowDecimals={false}
                                        />
                                        <ChartTooltip
                                            cursor={false}
                                            content={
                                                <ChartTooltipContent
                                                    indicator="dot"
                                                    formatter={(
                                                        value,
                                                        name,
                                                    ) => (
                                                        <div className="flex min-w-32 flex-1 items-center justify-between gap-3">
                                                            <span className="text-muted-foreground">
                                                                {name ===
                                                                'received_quantity'
                                                                    ? 'Barang masuk'
                                                                    : 'Barang keluar'}
                                                            </span>
                                                            <span className="font-mono font-medium text-foreground tabular-nums">
                                                                {formatNumber(
                                                                    Number(
                                                                        value,
                                                                    ),
                                                                )}
                                                            </span>
                                                        </div>
                                                    )}
                                                    className="rounded-[8px] bg-white text-slate-900 ring-slate-200"
                                                />
                                            }
                                        />
                                        <Bar
                                            dataKey="received_quantity"
                                            fill="var(--color-received_quantity)"
                                            radius={[4, 4, 0, 0]}
                                            maxBarSize={40}
                                        />
                                        <Bar
                                            dataKey="sold_quantity"
                                            fill="var(--color-sold_quantity)"
                                            radius={[4, 4, 0, 0]}
                                            maxBarSize={40}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section>
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
