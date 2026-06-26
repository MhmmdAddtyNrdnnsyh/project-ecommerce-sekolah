import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ReceiptText } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type OrderStatus = 'pending' | 'packed' | 'sent';

type PicketOrderItem = {
    id: number;
    order_id: number;
    buyer_name: string;
    seller_name: string;
    product_name: string;
    quantity: number;
    subtotal: number;
    status: { code: OrderStatus; label: string };
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
    sent: 'bg-emerald-50 text-emerald-700',
};

const nextStatus: Record<
    Exclude<OrderStatus, 'sent'>,
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
    const [statusError, setStatusError] = useState<string>();

    const advanceStatus = (item: PicketOrderItem) => {
        if (item.status.code === 'sent') {
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

    return (
        <>
            <Head title="Orders Picket" />
            <main className="min-h-dvh space-y-4 bg-slate-100 p-3 text-slate-950 sm:p-5">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
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
                        <Button
                            asChild
                            variant="outline"
                            className="h-10 w-fit rounded-[8px] border-slate-200 bg-white"
                        >
                            <Link href="/picket/pos">
                                <ArrowLeft className="size-4" />
                                Kembali ke POS
                            </Link>
                        </Button>
                    </div>
                </section>

                {(flash.success || flash.error || statusError) && (
                    <div
                        role="status"
                        className={cn(
                            'rounded-[8px] border px-4 py-3 text-sm',
                            flash.error || statusError
                                ? 'border-rose-200 bg-rose-50 text-rose-700'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        )}
                    >
                        {statusError || flash.error || flash.success}
                    </div>
                )}

                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="grid gap-3 border-b border-slate-100 pb-4 sm:grid-cols-2">
                        <Summary
                            label="Total item POS"
                            value={daily_report.total_sold}
                        />
                        <Summary
                            label="Total omzet POS"
                            value={formatRupiah(daily_report.total_revenue)}
                        />
                    </div>

                    <div className="mt-4 divide-y divide-slate-100">
                        {order_items.length === 0 ? (
                            <p className="py-8 text-center text-sm text-slate-500">
                                Belum ada order titipan UP.
                            </p>
                        ) : (
                            order_items.map((item) => (
                                <div
                                    key={item.id}
                                    className="grid gap-3 py-4 text-sm lg:grid-cols-[auto_1fr_auto_auto_auto] lg:items-center"
                                >
                                    <span className="font-semibold">
                                        #{item.order_id}
                                    </span>
                                    <div>
                                        <p className="font-semibold">
                                            {item.product_name}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            Buyer {item.buyer_name} · Seller{' '}
                                            {item.seller_name}
                                        </p>
                                    </div>
                                    <span className="text-slate-500">
                                        {item.quantity} item ·{' '}
                                        {formatRupiah(item.subtotal)}
                                    </span>
                                    <Badge
                                        className={cn(
                                            'w-fit rounded-full',
                                            statusStyles[item.status.code],
                                        )}
                                    >
                                        {item.status.label}
                                    </Badge>
                                    {item.status.code !== 'sent' ? (
                                        <Button
                                            type="button"
                                            size="sm"
                                            disabled={processingId === item.id}
                                            onClick={() => advanceStatus(item)}
                                            className="w-fit rounded-[8px]"
                                        >
                                            {processingId === item.id
                                                ? 'Memproses...'
                                                : nextStatus[item.status.code]
                                                      .action}
                                        </Button>
                                    ) : (
                                        <span className="text-sm font-medium text-emerald-700">
                                            Selesai
                                        </span>
                                    )}
                                </div>
                            ))
                        )}
                    </div>
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
