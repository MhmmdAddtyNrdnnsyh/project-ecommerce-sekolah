import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, PackageCheck, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { index as ordersIndex, updateStatus } from '@/routes/seller/orders';

type OrderStatus =
    | 'pending'
    | 'in_production'
    | 'ready'
    | 'packed'
    | 'sent'
    | 'completed';

type PaymentStatus = 'unpaid' | 'pending_confirmation' | 'paid' | 'rejected';

type OrderDetailProps = {
    orderItem: {
        id: number;
        source?: 'online' | 'offline';
        code?: string;
        order_id: number | string;
        buyer: { id: number | null; name: string };
        picket?: { id: number; name: string };
        up_jurusan?: { id: number; name: string };
        product: {
            id: number;
            name: string;
            slug: string;
            category: { id: number; name: string; slug: string };
        };
        managed_by_up_jurusan: boolean;
        is_pre_order: boolean;
        pre_order_estimate_days: number | null;
        pre_order_deadline: string | null;
        pre_order_min_quantity: number | null;
        pre_order_note: string | null;
        product_name: string;
        price: number;
        quantity: number;
        gross_amount?: number;
        commission_amount?: number;
        seller_amount?: number;
        subtotal: number;
        status: { code: OrderStatus; label: string };
        payment: {
            status: { code: PaymentStatus; label: string };
            method: { code: string; label: string };
            confirmed_at: string | null;
            rejection_reason: string | null;
        };
        created_at: string;
    };
};

const statusStyles: Record<OrderStatus, string> = {
    pending: 'bg-blue-50 text-blue-700',
    in_production: 'bg-violet-50 text-violet-700',
    ready: 'bg-cyan-50 text-cyan-700',
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

const nextStatus = {
    pending: { code: 'packed', action: 'Tandai sudah dikemas' },
    in_production: { code: 'ready', action: 'Tandai sudah siap' },
    ready: { code: 'sent', action: 'Tandai sudah dikirim' },
    packed: { code: 'sent', action: 'Tandai sudah dikirim' },
} as const;

const nextActionFor = (orderItem: OrderDetailProps['orderItem']) => {
    if (
        orderItem.status.code === 'sent' ||
        orderItem.status.code === 'completed'
    ) {
        return null;
    }

    if (orderItem.is_pre_order && orderItem.status.code === 'pending') {
        return { code: 'in_production' as const, action: 'Mulai produksi' };
    }

    return nextStatus[orderItem.status.code];
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function SellerOrdersShow({ orderItem }: OrderDetailProps) {
    const { flash } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [paymentProcessing, setPaymentProcessing] = useState(false);
    const [statusError, setStatusError] = useState<string>();
    const [paymentError, setPaymentError] = useState<string>();
    const isOffline = orderItem.source === 'offline';

    const advanceStatus = () => {
        if (
            isOffline ||
            orderItem.status.code === 'sent' ||
            orderItem.status.code === 'completed'
        ) {
            return;
        }

        const action = nextActionFor(orderItem);

        if (!action) {
            return;
        }

        setStatusError(undefined);

        router.put(
            updateStatus(orderItem.id),
            { status: action.code },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setStatusError(errors.status),
            },
        );
    };

    const approvePayment = () => {
        if (
            isOffline ||
            orderItem.managed_by_up_jurusan ||
            orderItem.payment.status.code === 'paid'
        ) {
            return;
        }

        setPaymentError(undefined);

        router.post(
            `/seller/orders/${orderItem.id}/payment/approve`,
            {},
            {
                preserveScroll: true,
                onStart: () => setPaymentProcessing(true),
                onFinish: () => setPaymentProcessing(false),
                onError: (errors) => setPaymentError(errors.payment),
            },
        );
    };

    const details = [
        ['Nomor transaksi', orderItem.code ?? `#${orderItem.order_id}`],
        ['Pembeli', orderItem.buyer.name],
        ['Produk', orderItem.product_name],
        ['Kategori', orderItem.product.category.name],
        ['Harga satuan', formatRupiah(orderItem.price)],
        ['Jumlah', String(orderItem.quantity)],
        ...(orderItem.is_pre_order
            ? [
                  ['Sistem', 'Pre-Order'],
                  [
                      'Estimasi',
                      `${orderItem.pre_order_estimate_days ?? '-'} hari`,
                  ],
                  ['Deadline PO', orderItem.pre_order_deadline ?? '-'],
                  [
                      'Minimum kuota',
                      orderItem.pre_order_min_quantity
                          ? `${orderItem.pre_order_min_quantity} pesanan`
                          : '-',
                  ],
              ]
            : []),
        [
            isOffline ? 'Hak seller' : 'Subtotal',
            formatRupiah(orderItem.subtotal),
        ],
        ['Pembayaran', `${orderItem.payment.status.label} (${orderItem.payment.method.label})`],
        ...(isOffline
            ? [
                  ['Omzet POS', formatRupiah(orderItem.gross_amount ?? 0)],
                  ['Komisi UP', formatRupiah(orderItem.commission_amount ?? 0)],
                  ['Picket', orderItem.picket?.name ?? '-'],
                  ['UP Jurusan', orderItem.up_jurusan?.name ?? '-'],
              ]
            : []),
        [
            'Waktu',
            new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'full',
                timeStyle: 'short',
            }).format(new Date(orderItem.created_at)),
        ],
    ];

    return (
        <>
            <Head title={`Pesanan ${orderItem.code ?? `#${orderItem.order_id}`}`} />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto max-w-3xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <ShoppingCart className="size-3.5" />{' '}
                                {isOffline
                                    ? 'Detail transaksi offline'
                                    : 'Detail fulfillment'}
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                {orderItem.code ?? `#${orderItem.order_id}`}
                            </h1>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-[8px]"
                        >
                            <Link href={ordersIndex()}>
                                <ArrowLeft className="size-4" /> Kembali
                            </Link>
                        </Button>
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

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-5">
                            <div>
                                <CardTitle>{orderItem.product_name}</CardTitle>
                                <CardDescription>
                                    {orderItem.product.slug}
                                </CardDescription>
                            </div>
                            <Badge
                                className={cn(
                                    'rounded-[6px]',
                                    statusStyles[orderItem.status.code],
                                )}
                            >
                                {orderItem.status.label}
                            </Badge>
                        </CardHeader>
                        <CardContent className="p-5">
                            <dl className="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                                {details.map(([label, value]) => (
                                    <div key={label}>
                                        <dt className="text-sm text-slate-500">
                                            {label}
                                        </dt>
                                        <dd className="mt-1 font-medium text-slate-950">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>

                            <div className="mt-6 border-t border-slate-100 pt-5">
                                {!isOffline &&
                                    !orderItem.managed_by_up_jurusan && (
                                        <div className="mb-5 flex flex-col gap-3 rounded-[8px] border border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-slate-950">
                                                    Pelunasan tunai
                                                </p>
                                                <Badge
                                                    className={cn(
                                                        'mt-2 rounded-[6px]',
                                                        paymentStatusStyles[
                                                            orderItem.payment
                                                                .status.code
                                                        ],
                                                    )}
                                                >
                                                    {
                                                        orderItem.payment
                                                            .status.label
                                                    }
                                                </Badge>
                                            </div>
                                            {orderItem.payment.status.code !==
                                                'paid' && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    disabled={paymentProcessing}
                                                    onClick={approvePayment}
                                                    className="rounded-[8px] border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                                                >
                                                    <CheckCircle2 className="size-4" />
                                                    {paymentProcessing
                                                        ? 'Memproses...'
                                                        : 'Tandai lunas'}
                                                </Button>
                                            )}
                                        </div>
                                )}

                                {isOffline ? (
                                    <p className="text-sm font-medium text-slate-600">
                                        Transaksi ini dicatat dari POS picket UP
                                        Jurusan. Seller menerima hak penjualan
                                        setelah komisi UP.
                                    </p>
                                ) : orderItem.managed_by_up_jurusan ? (
                                    <p className="text-sm font-medium text-slate-600">
                                        Status pengiriman dikelola oleh picket
                                        officer UP Jurusan. Seller hanya
                                        menerima notifikasi pesanan masuk.
                                    </p>
                                ) : orderItem.status.code === 'completed' ? (
                                    <p className="flex items-center gap-2 text-sm font-medium text-emerald-700">
                                        <PackageCheck className="size-4" />{' '}
                                        Fulfillment selesai.
                                    </p>
                                ) : orderItem.status.code === 'sent' ? (
                                    <p className="text-sm font-medium text-indigo-700">
                                        Pesanan sudah dikirim. Menunggu buyer
                                        mengonfirmasi barang diterima.
                                    </p>
                                ) : orderItem.is_pre_order &&
                                  orderItem.status.code === 'ready' ? (
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={advanceStatus}
                                        className="rounded-[8px]"
                                    >
                                        {processing
                                            ? 'Memproses...'
                                            : 'Tandai sudah dikirim'}
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={advanceStatus}
                                        className="rounded-[8px]"
                                    >
                                        {processing
                                            ? 'Memproses...'
                                            : nextActionFor(orderItem)?.action}
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

SellerOrdersShow.layout = {
    breadcrumbs: [{ title: 'Pesanan', href: ordersIndex() }],
};
