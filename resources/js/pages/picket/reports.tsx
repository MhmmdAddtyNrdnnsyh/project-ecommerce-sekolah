import { Form, Head, Link } from '@inertiajs/react';
import { FileText, ReceiptText } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type DailyReportItem = {
    id: number;
    code: string;
    receipt_url: string;
    sold_at: string | null;
    total_quantity: number;
    total_amount: number;
    commission_amount: number;
    seller_amount: number;
    products: {
        product_name: string;
        source: string;
        quantity: number;
        unit_price: number;
        subtotal: number;
    }[];
};

type Props = {
    errors?: {
        report?: string;
    };
    daily_report: {
        date: string;
        total_sold: number;
        total_revenue: number;
        submitted_at: string | null;
        items: DailyReportItem[];
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDateTime = (value: string | null) =>
    value ? new Date(value).toLocaleString('id-ID') : '-';

export default function PicketReports({ errors, daily_report }: Props) {
    return (
        <>
            <Head title="Reports Picket" />
            <main className="min-h-dvh space-y-4 bg-slate-50 p-4 text-slate-950 sm:p-6">
                <section className="rounded-[8px] border border-slate-100 bg-white p-5 shadow-sm">
                    <Badge className="mb-3 rounded-[6px] bg-blue-50 text-blue-700">
                        <FileText className="size-3.5" />
                        {daily_report.date}
                    </Badge>
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Laporan Penjualan
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Daftar transaksi POS picket hari ini.
                            </p>
                        </div>
                        <Form action="/picket/up-jurusan/report" method="post">
                            {({ processing }) => (
                                <>
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing ||
                                            Boolean(daily_report.submitted_at)
                                        }
                                        className="h-10"
                                    >
                                        <ReceiptText className="size-4" />
                                        {daily_report.submitted_at
                                            ? 'Laporan dibuat'
                                            : 'Buat laporan'}
                                    </Button>
                                    <InputError message={errors?.report} />
                                </>
                            )}
                        </Form>
                    </div>
                </section>

                <section className="grid gap-3 md:grid-cols-2">
                    <Summary
                        label="Total item terjual"
                        value={daily_report.total_sold}
                    />
                    <Summary
                        label="Total omzet"
                        value={formatRupiah(daily_report.total_revenue)}
                    />
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-100 bg-white shadow-sm">
                    <div className="border-b border-slate-100 p-5">
                        <h2 className="font-semibold">Transaksi laporan</h2>
                    </div>
                    {daily_report.items.length === 0 ? (
                        <div className="p-5">
                            <p className="rounded-[8px] border border-dashed border-slate-200 py-8 text-center text-sm text-slate-500">
                                Belum ada data untuk dilaporkan.
                            </p>
                        </div>
                    ) : (
                        <Table className="[&_tbody_tr:nth-child(even)]:bg-slate-50/70">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Transaksi</TableHead>
                                    <TableHead>Produk</TableHead>
                                    <TableHead className="text-right">
                                        Item
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Omzet
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Komisi UP
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Hak Seller
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Nota
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {daily_report.items.map((item) => (
                                    <TableRow
                                        key={item.code}
                                        className="hover:bg-blue-50/50"
                                    >
                                        <TableCell className="min-w-52 whitespace-normal">
                                            <p className="font-semibold text-slate-950">
                                                {item.code}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {formatDateTime(item.sold_at)}
                                            </p>
                                        </TableCell>
                                        <TableCell className="min-w-72">
                                            <div className="space-y-2">
                                                {item.products.map(
                                                    (product) => (
                                                        <div
                                                            key={`${item.code}-${product.product_name}-${product.source}`}
                                                            className="rounded-[6px] border border-slate-200 bg-white p-2"
                                                        >
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <p className="font-medium text-slate-950">
                                                                    {
                                                                        product.product_name
                                                                    }
                                                                </p>
                                                                <Badge
                                                                    className={`rounded-[6px] ${
                                                                        product.source ===
                                                                        'Produk UP'
                                                                            ? 'bg-blue-50 text-blue-700'
                                                                            : 'bg-emerald-50 text-emerald-700'
                                                                    }`}
                                                                >
                                                                    {
                                                                        product.source
                                                                    }
                                                                </Badge>
                                                            </div>
                                                            <p className="mt-1 text-xs text-slate-500">
                                                                {
                                                                    product.quantity
                                                                }{' '}
                                                                x{' '}
                                                                {formatRupiah(
                                                                    product.unit_price,
                                                                )}{' '}
                                                                ={' '}
                                                                {formatRupiah(
                                                                    product.subtotal,
                                                                )}
                                                            </p>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {item.total_quantity} item
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatRupiah(item.total_amount)}
                                        </TableCell>
                                        <TableCell className="text-right text-blue-700 tabular-nums">
                                            {formatRupiah(
                                                item.commission_amount,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-emerald-700 tabular-nums">
                                            {formatRupiah(item.seller_amount)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                                className="w-fit"
                                            >
                                                <Link href={item.receipt_url}>
                                                    <ReceiptText className="size-4" />
                                                    Detail
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </section>
            </main>
        </>
    );
}

function Summary({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </div>
    );
}
