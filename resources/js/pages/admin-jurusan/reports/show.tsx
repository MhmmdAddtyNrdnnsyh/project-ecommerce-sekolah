import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, PackageCheck, ReceiptText, Wallet } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Report = {
    id: number;
    date: string;
    picket_name: string;
    up_jurusan_name: string;
    total_sold: number;
    total_revenue: number;
    submitted_at: string;
};

type Transaction = {
    id: string;
    code: string;
    total_quantity: number;
    total_amount: number;
    commission_amount: number;
    seller_amount: number;
    created_at: string | null;
    items: {
        id: number;
        product_name: string;
        quantity: number;
        unit_price: number;
        subtotal: number;
    }[];
};

type Props = {
    report: Report;
    transactions: Transaction[];
};

type SummaryProps = {
    label: string;
    value: string | number;
    icon: LucideIcon;
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDateTime = (value: string | null) =>
    value ? new Date(value).toLocaleString('id-ID') : '-';

export default function AdminJurusanReportDetail({
    report,
    transactions,
}: Props) {
    return (
        <>
            <Head title={`Detail Laporan ${report.date}`} />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <Button asChild variant="outline" size="sm">
                        <Link
                            href={`/admin-jurusan/reports?date=${report.date}`}
                        >
                            <ArrowLeft className="size-4" />
                            Kembali
                        </Link>
                    </Button>
                    <div className="mt-5 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                {report.up_jurusan_name}
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                Detail Laporan Picket
                            </h1>
                            <p className="mt-1 text-sm leading-6 text-slate-500">
                                {report.picket_name} - {report.date} - dikirim{' '}
                                {formatDateTime(report.submitted_at)}
                            </p>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-3">
                    <Summary
                        label="Transaksi"
                        value={transactions.length}
                        icon={ReceiptText}
                    />
                    <Summary
                        label="Item Terjual"
                        value={report.total_sold}
                        icon={PackageCheck}
                    />
                    <Summary
                        label="Omzet"
                        value={formatRupiah(report.total_revenue)}
                        icon={Wallet}
                    />
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 p-4">
                        <h2 className="font-semibold text-slate-950">
                            Transaksi dalam laporan
                        </h2>
                    </div>

                    {transactions.length === 0 ? (
                        <p className="p-10 text-center text-sm text-slate-500">
                            Belum ada transaksi di laporan ini.
                        </p>
                    ) : (
                        <Table className="[&_tbody_tr:nth-child(even)]:bg-slate-50/70">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Transaksi</TableHead>
                                    <TableHead className="text-right">
                                        Qty
                                    </TableHead>
                                    <TableHead>Produk</TableHead>
                                    <TableHead className="text-right">
                                        Omzet
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Komisi UP
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Hak Seller
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transactions.map((transaction) => (
                                    <TableRow
                                        key={transaction.id}
                                        className="hover:bg-blue-50/50"
                                    >
                                        <TableCell className="min-w-48">
                                            <p className="font-semibold text-slate-950">
                                                {transaction.code}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {formatDateTime(
                                                    transaction.created_at,
                                                )}
                                            </p>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {transaction.total_quantity} item
                                        </TableCell>
                                        <TableCell className="min-w-72 whitespace-normal">
                                            <div className="flex flex-wrap gap-1.5">
                                                {transaction.items.map(
                                                    (item) => (
                                                        <span
                                                            key={item.id}
                                                            className="rounded-[6px] bg-slate-100 px-2 py-1 text-xs text-slate-700"
                                                        >
                                                            {item.product_name}{' '}
                                                            x{item.quantity} -{' '}
                                                            {formatRupiah(
                                                                item.subtotal,
                                                            )}
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right font-semibold text-slate-950 tabular-nums">
                                            {formatRupiah(
                                                transaction.total_amount,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-blue-700 tabular-nums">
                                            {formatRupiah(
                                                transaction.commission_amount,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-emerald-700 tabular-nums">
                                            {formatRupiah(
                                                transaction.seller_amount,
                                            )}
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

function Summary({ label, value, icon: Icon }: SummaryProps) {
    return (
        <div className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm text-slate-500">{label}</p>
                <span className="grid size-8 place-items-center rounded-[8px] bg-slate-100 text-slate-600">
                    <Icon className="size-4" />
                </span>
            </div>
            <p className="mt-3 text-lg font-semibold text-slate-950 tabular-nums">
                {String(value)}
            </p>
        </div>
    );
}
