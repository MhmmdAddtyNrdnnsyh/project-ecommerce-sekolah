import { Head, Link } from '@inertiajs/react';
import { Package, ReceiptText, ShoppingCart, Store } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type PosProduct = {
    id: number;
    product_name: string;
    available_quantity: number;
};

type DailyReportItem = {
    product_name: string;
    quantity: number;
    subtotal: number;
};

type Props = {
    up_jurusan: { id: number; name: string } | null;
    pos_products: PosProduct[];
    daily_report: {
        date: string;
        total_sold: number;
        total_revenue: number;
        items: DailyReportItem[];
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function PicketDashboard({
    up_jurusan,
    pos_products,
    daily_report,
}: Props) {
    const lowStock = pos_products.filter(
        (product) => product.available_quantity <= 3,
    ).length;

    return (
        <>
            <Head title="Dashboard Picket" />
            <main className="min-h-dvh space-y-4 bg-slate-100 p-3 text-slate-950 sm:p-5">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
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
                        <Button
                            asChild
                            className="h-10 w-fit rounded-[8px] bg-blue-600 text-white hover:bg-blue-700"
                        >
                            <Link href="/picket/pos">
                                <ShoppingCart className="size-4" />
                                Buka POS
                            </Link>
                        </Button>
                    </div>
                </section>

                <section className="grid gap-3 md:grid-cols-4">
                    <Summary label="Produk aktif" value={pos_products.length} />
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
                            daily_report.items.slice(0, 5).map((item) => (
                                <Row
                                    key={item.product_name}
                                    label={item.product_name}
                                    value={`${item.quantity} item`}
                                />
                            ))
                        )}
                    </Panel>
                </section>
            </main>
        </>
    );
}

function Summary({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold">{value}</p>
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
        <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="mb-4 flex items-center gap-2 font-semibold">
                <span className="text-blue-700 [&_svg]:size-5">{icon}</span>
                {title}
            </h2>
            <div className="space-y-3">{children}</div>
        </section>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-[8px] border border-slate-100 px-3 py-2 text-sm">
            <span className="line-clamp-1 font-medium">{label}</span>
            <span className="shrink-0 text-slate-500">{value}</span>
        </div>
    );
}
