import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ReceiptText } from 'lucide-react';
import { useState } from 'react';
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
import { cn } from '@/lib/utils';

type OrderStatus = 'pending' | 'packed' | 'sent' | 'completed';
type PaymentStatus = 'unpaid' | 'pending_confirmation' | 'paid' | 'rejected';

type PicketOrderItem = {
    id: number;
    code: string;
    order_id: number;
    buyer_name: string;
    seller_name: string;
    product_name: string;
    quantity: number;
    subtotal: number;
    status: { code: OrderStatus; label: string };
    payment: {
        status: { code: PaymentStatus; label: string };
        method: { code: string; label: string };
        confirmed_at: string | null;
        rejection_reason: string | null;
    };
};

type Props = {
    daily_report: {
        date: string;
        total_sold: number;
        total_revenue: number;
    };
    order_items: PicketOrderItem[];
};

const statusStyles: Record<OrderStatus, string> = {
    pending: 'bg-blue-50 text-blue-700',
    packed: 'bg-amber-50 text-amber-700',
    sent: 'bg-indigo-50 text-indigo-700',
    completed: 'bg-emerald-50 text-emerald-700',
};

const paymentStatusStyles: Record<PaymentStatus, string> = {
    unpaid: 'bg-slate-100 text-slate-700',
    pending_confirmation: 'bg-amber-50 text-amber-700',
    paid: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-rose-50 text-rose-700',
};

const nextStatus: Record<
    Exclude<OrderStatus, 'sent' | 'completed'>,
    { code: OrderStatus; action: string }
> = {
    pending: { code: 'packed', action: 'Tandai dikemas' },
    packed: { code: 'sent', action: 'Tandai dikirim' },
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function PicketOrders({ daily_report, order_items }: Props) {
    const { flash } = usePage().props;
    const [processingId, setProcessingId] = useState<number>();
    const [paymentProcessingId, setPaymentProcessingId] = useState<number>();
    const [statusError, setStatusError] = useState<string>();
    const [paymentError, setPaymentError] = useState<string>();

    const advanceStatus = (item: PicketOrderItem) => {
        if (item.status.code === 'sent' || item.status.code === 'completed') {
            return;
        }

        setStatusError(undefined);

        router.put(
            `/picket/orders/${item.id}/status`,
            { status: nextStatus[item.status.code].code },
            {
                preserveScroll: true,
                onStart: () => setProcessingId(item.id),
                onFinish: () => setProcessingId(undefined),
                onError: (errors) => setStatusError(errors.status),
            },
        );
    };

    const approvePayment = (item: PicketOrderItem) => {
        if (item.payment.status.code === 'paid') {
            return;
        }

        setPaymentError(undefined);

        router.post(
            `/picket/orders/${item.id}/payment/approve`,
            {},
            {
                preserveScroll: true,
                onStart: () => setPaymentProcessingId(item.id),
                onFinish: () => setPaymentProcessingId(undefined),
                onError: (errors) => setPaymentError(errors.payment),
            },
        );
    };

    return (
        <>
            <Head title="Orders Picket" />
            <main className="min-h-dvh space-y-4 bg-slate-50 p-4 text-slate-950 sm:p-6">
                <section className="rounded-[8px] border border-slate-100 bg-white p-5 shadow-sm">
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div>
                            <Badge className="mb-3 rounded-[6px] bg-blue-50 text-blue-700">
                                <ReceiptText className="size-3.5" />
                                {daily_report.date}
                            </Badge>
                            <h1 className="text-2xl font-semibold">
                                Orders Titipan UP
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Status pengiriman produk titipan dikelola oleh
                                picket officer.
                            </p>
                        </div>
                        <Button asChild variant="outline" className="w-fit">
                            <Link href="/picket/pos">
                                <ArrowLeft className="size-4" />
                                Kembali ke POS
                            </Link>
                        </Button>
                    </div>
                </section>

                {(flash.success ||
                    flash.error ||
                    statusError ||
                    paymentError) && (
                    <div
                        role="status"
                        className={cn(
                            'rounded-[8px] border px-4 py-3 text-sm',
                            flash.error || statusError || paymentError
                                ? 'border-rose-200 bg-rose-50 text-rose-700'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        )}
                    >
                        {statusError ||
                            paymentError ||
                            flash.error ||
                            flash.success}
                    </div>
                )}

                <section className="overflow-hidden rounded-[8px] border border-slate-100 bg-white shadow-sm">
                    <div className="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-2">
                        <Summary
                            label="Total item POS"
                            value={daily_report.total_sold}
                        />
                        <Summary
                            label="Total omzet POS"
                            value={formatRupiah(daily_report.total_revenue)}
                        />
                    </div>

                    {order_items.length === 0 ? (
                        <div className="p-5">
                            <p className="rounded-[8px] border border-dashed border-slate-200 py-10 text-center text-sm text-slate-500">
                                Belum ada order titipan UP.
                            </p>
                        </div>
                    ) : (
                        <Table className="[&_tbody_tr:nth-child(even)]:bg-slate-50/70">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Transaksi</TableHead>
                                    <TableHead>Produk</TableHead>
                                    <TableHead>Pembeli</TableHead>
                                    <TableHead className="text-right">
                                        Qty
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Subtotal
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Pembayaran</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {order_items.map((item) => (
                                    <TableRow
                                        key={item.id}
                                        className="hover:bg-blue-50/50"
                                    >
                                        <TableCell className="font-semibold text-slate-950">
                                            {item.code}
                                        </TableCell>
                                        <TableCell className="min-w-64 whitespace-normal">
                                            <p className="font-semibold text-slate-950">
                                                {item.product_name}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                Seller {item.seller_name}
                                            </p>
                                        </TableCell>
                                        <TableCell className="min-w-40 whitespace-normal">
                                            {item.buyer_name}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {item.quantity} item
                                        </TableCell>
                                        <TableCell className="text-right font-semibold tabular-nums">
                                            {formatRupiah(item.subtotal)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                className={cn(
                                                    'w-fit rounded-[6px]',
                                                    statusStyles[
                                                        item.status.code
                                                    ],
                                                )}
                                            >
                                                {item.status.label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="space-y-1">
                                                <Badge
                                                    className={cn(
                                                        'w-fit rounded-[6px]',
                                                        paymentStatusStyles[
                                                            item.payment.status
                                                                .code
                                                        ],
                                                    )}
                                                >
                                                    {item.payment.status.label}
                                                </Badge>
                                                <p className="text-xs text-slate-500">
                                                    {item.payment.method.label}
                                                </p>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                {item.payment.status.code !==
                                                    'paid' && (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            paymentProcessingId ===
                                                            item.id
                                                        }
                                                        onClick={() =>
                                                            approvePayment(item)
                                                        }
                                                        className="rounded-[8px] border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                                                    >
                                                        <CheckCircle2 className="size-3.5" />
                                                        {paymentProcessingId ===
                                                        item.id
                                                            ? 'Memproses...'
                                                            : 'Tandai lunas'}
                                                    </Button>
                                                )}
                                                {item.status.code ===
                                                    'pending' ||
                                                item.status.code ===
                                                    'packed' ? (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        disabled={
                                                            processingId ===
                                                            item.id
                                                        }
                                                        onClick={() =>
                                                            advanceStatus(item)
                                                        }
                                                        className="w-fit"
                                                    >
                                                        {processingId ===
                                                        item.id
                                                            ? 'Memproses...'
                                                            : nextStatus[
                                                                  item.status
                                                                      .code
                                                              ].action}
                                                    </Button>
                                                ) : (
                                                    <span
                                                        className={cn(
                                                            'text-sm font-medium',
                                                            item.status.code ===
                                                                'completed'
                                                                ? 'text-emerald-700'
                                                                : 'text-indigo-700',
                                                        )}
                                                    >
                                                        {item.status.code ===
                                                        'completed'
                                                            ? 'Selesai'
                                                            : 'Menunggu buyer'}
                                                    </span>
                                                )}
                                            </div>
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
        <div className="rounded-[8px] bg-slate-50 p-4">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </div>
    );
}
