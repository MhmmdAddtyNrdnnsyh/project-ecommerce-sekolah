import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Check, Eye, Inbox, X } from 'lucide-react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

type Props = {
    consignments: {
        id: number;
        seller_name: string;
        product_name: string;
        up_jurusan_name: string;
        requested_quantity: number;
        status: { code: string; label: string };
    }[];
};

export default function AdminJurusanConsignments({ consignments }: Props) {
    const { flash } = usePage().props;

    return (
        <>
            <Head title="Request Titip Barang" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                Review Seller
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                Request Titip Barang
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Setujui barang yang layak masuk UP Jurusan atau
                                tolak sebelum stok tercatat.
                            </p>
                        </div>
                        <div className="rounded-[8px] border border-slate-200 bg-slate-50 px-4 py-3">
                            <p className="text-xs font-medium text-slate-500 uppercase">
                                Total request
                            </p>
                            <p className="mt-1 text-2xl font-semibold text-slate-950 tabular-nums">
                                {consignments.length}
                            </p>
                        </div>
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

                <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    {consignments.length > 0 && (
                        <div className="hidden grid-cols-[1.4fr_1fr_1fr_auto] gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 text-xs font-medium text-slate-500 uppercase md:grid">
                            <span>Produk</span>
                            <span>UP Jurusan</span>
                            <span>Status</span>
                            <span className="text-right">Aksi</span>
                        </div>
                    )}
                    {consignments.length === 0 && (
                        <div className="grid place-items-center p-10 text-center">
                            <span className="grid size-12 place-items-center rounded-[8px] bg-slate-100 text-slate-500">
                                <Inbox className="size-6" />
                            </span>
                            <p className="font-medium text-slate-950">
                                Belum ada request titip barang.
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                Request seller akan muncul di sini setelah
                                mereka memilih titip ke UP Jurusan.
                            </p>
                        </div>
                    )}
                    {consignments.map((item) => (
                        <div
                            key={item.id}
                            className="grid gap-3 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-[1.4fr_1fr_1fr_auto] md:items-center"
                        >
                            <div>
                                <p className="font-medium text-slate-950">
                                    {item.product_name}
                                </p>
                                <p className="text-slate-500">
                                    {item.seller_name}
                                </p>
                            </div>
                            <span className="text-slate-600">
                                {item.up_jurusan_name}
                            </span>
                            <span className="w-fit rounded-[6px] bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                {item.requested_quantity} item ·{' '}
                                {item.status.label}
                            </span>
                            <div className="flex flex-wrap justify-start gap-2 md:justify-end">
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={`/admin-jurusan/consignments/${item.id}`}
                                    >
                                        <Eye className="size-4" />
                                        Detail
                                    </Link>
                                </Button>
                                {item.status.code === 'pending_approval' && (
                                    <>
                                        <Form
                                            action={`/admin-jurusan/consignments/${item.id}/approve`}
                                            method="post"
                                        >
                                            <Button type="submit" size="sm">
                                                <Check className="size-4" />
                                                Approve
                                            </Button>
                                        </Form>
                                        <RejectConsignmentDialog item={item} />
                                    </>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </main>
        </>
    );
}

function RejectConsignmentDialog({
    item,
}: {
    item: Props['consignments'][number];
}) {
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <Button type="button" size="sm" variant="outline">
                    <X className="size-4" />
                    Reject
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Tolak request titip?</AlertDialogTitle>
                    <AlertDialogDescription>
                        {item.product_name} dari {item.seller_name} akan ditolak
                        dan status produk seller ikut menjadi rejected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <Form
                    action={`/admin-jurusan/consignments/${item.id}/reject`}
                    method="post"
                    className="space-y-4"
                >
                    <Textarea
                        name="rejection_reason"
                        required
                        maxLength={1000}
                        placeholder="Tulis alasan penolakan untuk seller"
                    />
                    <AlertDialogFooter>
                        <AlertDialogCancel asChild>
                            <Button type="button" variant="outline">
                                Batal
                            </Button>
                        </AlertDialogCancel>
                        <Button type="submit" variant="destructive">
                            Reject
                        </Button>
                    </AlertDialogFooter>
                </Form>
            </AlertDialogContent>
        </AlertDialog>
    );
}
