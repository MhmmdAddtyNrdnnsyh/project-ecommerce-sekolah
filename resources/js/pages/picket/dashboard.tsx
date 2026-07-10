import { Head, Link, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardCheck,
    Package,
    ReceiptText,
    ShoppingCart,
    Store,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type PosProduct = {
    id: number;
    product_name: string;
    available_quantity: number;
};

type DailyReportTransaction = {
    id: number;
    code: string;
    receipt_url: string;
    total_quantity: number;
    total_amount: number;
    commission_amount: number;
    seller_amount: number;
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
        status: { code: 'open' | 'submitted'; label: string };
        total_sold: number;
        total_revenue: number;
        submitted_at?: string | null;
        items: DailyReportTransaction[];
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatTime = (value: string) =>
    new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));

export default function PicketDashboard({
    up_jurusan,
    pos_products,
    consignments,
    daily_report,
}: Props) {
    const { flash } = usePage().props;
    const isSubmitted = daily_report.status.code === 'submitted';
    const lowStock = pos_products.filter(
        (product) => product.available_quantity <= 3,
    );
    const awaitingReceive = consignments.filter(
        (consignment) =>
            consignment.status.code === 'approved' &&
            consignment.received_quantity < consignment.requested_quantity,
    );

    return (
        <>
            <Head title="Dashboard Picket" />
            <main className="min-h-dvh space-y-5 bg-slate-50 p-4 text-slate-950 sm:p-6">
                <header className="rounded-[8px] border border-slate-100 bg-white p-5 shadow-sm">
                    <Badge className="mb-3 rounded-[6px] bg-blue-50 text-blue-700">
                        <Store className="size-3.5" />
                        {up_jurusan?.name ?? 'UP Jurusan'}
                    </Badge>
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Dashboard Picket
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Selesaikan penerimaan, transaksi POS, dan
                                laporan hari ini.
                            </p>
                        </div>
                        {isSubmitted ? (
                            <Badge className="w-fit rounded-[6px] bg-emerald-50 px-3 py-2 text-emerald-700">
                                <CheckCircle2 className="size-4" />
                                POS hari ini ditutup
                            </Badge>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                <Button asChild>
                                    <Link href="/picket/pos">
                                        <ShoppingCart className="size-4" />
                                        Buka POS
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href="/picket/reports">
                                        <ClipboardCheck className="size-4" />
                                        Kirim Laporan
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>
                </header>

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

                {isSubmitted && daily_report.submitted_at && (
                    <div className="flex items-start gap-3 rounded-[8px] border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        <CheckCircle2 className="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p className="font-semibold">
                                Laporan sudah dikirim
                            </p>
                            <p className="mt-1 text-emerald-700">
                                Dikirim pukul{' '}
                                {formatTime(daily_report.submitted_at)}.
                                Transaksi POS baru untuk hari ini sudah ditutup.
                            </p>
                        </div>
                    </div>
                )}

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Summary
                        label="Status Laporan"
                        value={daily_report.status.label}
                        href="/picket/reports"
                    />
                    <Summary
                        label="Transaksi Hari Ini"
                        value={daily_report.items.length}
                    />
                    <Summary
                        label="Item Terjual Hari Ini"
                        value={daily_report.total_sold}
                    />
                    <Summary
                        label="Omzet POS Hari Ini"
                        value={formatRupiah(daily_report.total_revenue)}
                    />
                </section>

                <section className="grid gap-4 lg:grid-cols-3">
                    <Panel title="Menunggu Diterima" icon={<ClipboardCheck />}>
                        {awaitingReceive.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Semua barang yang disetujui sudah diterima.
                            </p>
                        ) : (
                            awaitingReceive
                                .slice(0, 5)
                                .map((item) => (
                                    <Row
                                        key={item.id}
                                        label={item.product_name}
                                        value={`${item.received_quantity}/${item.requested_quantity} item`}
                                        href="/picket/receiving"
                                    />
                                ))
                        )}
                    </Panel>

                    <Panel title="Perhatian Stok" icon={<Package />}>
                        {lowStock.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Tidak ada stok menipis.
                            </p>
                        ) : (
                            lowStock
                                .slice(0, 5)
                                .map((product) => (
                                    <Row
                                        key={product.id}
                                        label={product.product_name}
                                        value={`Stok ${product.available_quantity}`}
                                    />
                                ))
                        )}
                    </Panel>

                    <Panel title="Nota Terbaru" icon={<ReceiptText />}>
                        {daily_report.items.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Belum ada transaksi POS hari ini.
                            </p>
                        ) : (
                            daily_report.items
                                .slice(0, 5)
                                .map((item) => (
                                    <Row
                                        key={item.id}
                                        label={item.code}
                                        value={`${item.total_quantity} item`}
                                        href={item.receipt_url}
                                    />
                                ))
                        )}
                    </Panel>
                </section>

                <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                    <CardHeader className="p-5 pb-0">
                        <CardTitle>Ringkasan Setoran Hari Ini</CardTitle>
                        <CardDescription>
                            Rincian transaksi yang masuk ke laporan picket.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
                        <Metric
                            label="Omzet POS"
                            value={formatRupiah(daily_report.total_revenue)}
                        />
                        <Metric
                            label="Hak Seller Titipan"
                            value={formatRupiah(
                                daily_report.items.reduce(
                                    (total, item) => total + item.seller_amount,
                                    0,
                                ),
                            )}
                        />
                        <Metric
                            label="Pendapatan UP"
                            value={formatRupiah(
                                daily_report.items.reduce(
                                    (total, item) =>
                                        total + item.commission_amount,
                                    0,
                                ),
                            )}
                        />
                        <Metric
                            label="Jumlah Nota"
                            value={`${daily_report.items.length} transaksi`}
                        />
                    </CardContent>
                </Card>
            </main>
        </>
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
            <p className="mt-2 text-xl font-semibold break-words tabular-nums">
                {value}
            </p>
        </>
    );

    return href ? (
        <Link
            href={href}
            className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm transition hover:border-blue-200"
        >
            {content}
        </Link>
    ) : (
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
    const content = (
        <>
            <span className="line-clamp-1 font-medium">{label}</span>
            <span className="shrink-0 text-slate-500">{value}</span>
        </>
    );
    const className =
        'flex items-center justify-between gap-3 rounded-[8px] border border-slate-100 px-3 py-2 text-sm transition hover:border-blue-200 hover:bg-blue-50/50';

    return href ? (
        <Link href={href} className={className}>
            {content}
        </Link>
    ) : (
        <div className={className}>{content}</div>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[8px] border border-slate-100 p-3">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 font-semibold break-words tabular-nums">
                {value}
            </p>
        </div>
    );
}
