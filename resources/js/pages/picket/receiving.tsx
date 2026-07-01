import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, PackageCheck, Store } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

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
    consignments: Consignment[];
};

export default function PicketReceiving({ up_jurusan, consignments }: Props) {
    const { flash } = usePage().props;
    const awaitingReceive = consignments.filter(
        (consignment) =>
            consignment.status.code === 'approved' &&
            consignment.received_quantity < consignment.requested_quantity,
    );

    return (
        <>
            <Head title="Terima Barang Titipan" />
            <main className="min-h-dvh space-y-4 bg-slate-50 p-4 text-slate-950 sm:p-6">
                <section className="rounded-[8px] border border-slate-100 bg-white p-5 shadow-sm">
                    <Badge className="mb-3 rounded-[6px] bg-blue-50 text-blue-700">
                        <Store className="size-3.5" />
                        {up_jurusan?.name ?? 'UP Jurusan'}
                    </Badge>
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Terima Barang Titipan
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Catat barang fisik yang sudah datang setelah
                                request disetujui admin jurusan.
                            </p>
                        </div>
                        <Button asChild variant="outline" className="w-fit">
                            <Link href="/picket/dashboard">
                                <ArrowLeft className="size-4" />
                                Dashboard
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

                <section className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-semibold">
                                Barang Menunggu Diterima
                            </h2>
                            <p className="text-sm text-slate-500">
                                {awaitingReceive.length} request siap dicatat.
                            </p>
                        </div>
                        <PackageCheck className="size-5 text-blue-700" />
                    </div>

                    {awaitingReceive.length === 0 ? (
                        <div className="rounded-[8px] border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                            Tidak ada barang approved yang menunggu diterima.
                        </div>
                    ) : (
                        <div className="grid gap-3">
                            {awaitingReceive.map((consignment) => {
                                const remaining =
                                    consignment.requested_quantity -
                                    consignment.received_quantity;

                                return (
                                    <Form
                                        key={consignment.id}
                                        action={`/picket/up-jurusan/consignments/${consignment.id}/receive`}
                                        method="post"
                                        className="grid gap-3 rounded-[8px] border border-slate-100 p-4 text-sm md:grid-cols-[1fr_11rem_auto] md:items-center"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {consignment.product_name}
                                            </p>
                                            <p className="mt-1 text-slate-500">
                                                {consignment.seller_name} ·
                                                diterima{' '}
                                                {consignment.received_quantity}/
                                                {consignment.requested_quantity}{' '}
                                                item
                                            </p>
                                        </div>
                                        <input
                                            name="quantity"
                                            type="number"
                                            min="1"
                                            max={remaining}
                                            defaultValue={remaining}
                                            required
                                            className="h-11 rounded-[8px] border border-slate-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/20"
                                        />
                                        <Button type="submit" className="h-11">
                                            Terima Barang
                                        </Button>
                                    </Form>
                                );
                            })}
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}
