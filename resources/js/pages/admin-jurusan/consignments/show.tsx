import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Props = {
    consignment: {
        id: number;
        seller: { id: number; name: string; email: string };
        product: {
            id: number;
            name: string;
            description: string;
            price: number;
            stock: number;
        };
        up_jurusan: { id: number; name: string };
        requested_quantity: number;
        received_quantity: number;
        sold_quantity: number;
        commission_rate: number | null;
        seller_earnings: number;
        paid_amount: number;
        unpaid_amount: number;
        status: { code: string; label: string };
        created_at: string | null;
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function AdminJurusanConsignmentShow({ consignment }: Props) {
    return (
        <>
            <Head title={`Request ${consignment.product.name}`} />
            <main className="space-y-6 p-4 sm:p-6">
                <section className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Detail Request Titip
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {consignment.product.name} -{' '}
                            {consignment.status.label}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href="/admin-jurusan/consignments">
                            <ArrowLeft className="size-4" />
                            Kembali
                        </Link>
                    </Button>
                </section>

                <section className="grid gap-4 lg:grid-cols-[1fr_20rem]">
                    <div className="rounded-[8px] border border-slate-200 bg-white p-5">
                        <h2 className="font-semibold text-slate-950">Produk</h2>
                        <div className="mt-4 grid gap-3 text-sm">
                            <Info
                                label="Nama"
                                value={consignment.product.name}
                            />
                            <Info
                                label="Harga"
                                value={formatRupiah(consignment.product.price)}
                            />
                            <Info
                                label="Stok Produk"
                                value={String(consignment.product.stock)}
                            />
                            <Info
                                label="Deskripsi"
                                value={consignment.product.description}
                            />
                        </div>
                    </div>

                    <div className="rounded-[8px] border border-slate-200 bg-white p-5">
                        <h2 className="font-semibold text-slate-950">
                            Request
                        </h2>
                        <div className="mt-4 grid gap-3 text-sm">
                            <Info
                                label="Seller"
                                value={`${consignment.seller.name} (${consignment.seller.email})`}
                            />
                            <Info
                                label="UP Jurusan"
                                value={consignment.up_jurusan.name}
                            />
                            <Info
                                label="Jumlah Request"
                                value={String(consignment.requested_quantity)}
                            />
                            <Info
                                label="Diterima"
                                value={String(consignment.received_quantity)}
                            />
                            <Info
                                label="Keluar"
                                value={String(consignment.sold_quantity)}
                            />
                            <Info
                                label="Komisi UP"
                                value={
                                    consignment.commission_rate === null
                                        ? 'Belum ditetapkan'
                                        : `${consignment.commission_rate}%`
                                }
                            />
                            <Info
                                label="Saldo Seller"
                                value={formatRupiah(consignment.unpaid_amount)}
                            />
                            <Info
                                label="Tanggal"
                                value={
                                    consignment.created_at
                                        ? new Date(
                                              consignment.created_at,
                                          ).toLocaleString('id-ID')
                                        : '-'
                                }
                            />
                        </div>

                        {consignment.status.code === 'pending_approval' && (
                            <div className="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4">
                                <Form
                                    action={`/admin-jurusan/consignments/${consignment.id}/approve`}
                                    method="post"
                                    className="flex flex-wrap items-end gap-2"
                                >
                                    <div className="grid gap-1">
                                        <label
                                            htmlFor="commission_rate"
                                            className="text-xs font-medium text-slate-600"
                                        >
                                            Komisi UP (%)
                                        </label>
                                        <Input
                                            id="commission_rate"
                                            name="commission_rate"
                                            type="number"
                                            min={0}
                                            max={100}
                                            defaultValue={10}
                                            required
                                            className="w-28"
                                        />
                                    </div>
                                    <Button type="submit">Approve</Button>
                                </Form>
                                <RejectConsignmentDialog
                                    consignment={consignment}
                                />
                            </div>
                        )}
                        {(consignment.status.code === 'approved' ||
                            consignment.status.code === 'received') && (
                            <div className="mt-5 rounded-[8px] border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
                                Barang fisik diterima oleh picket officer lewat
                                halaman Picket Receiving. Admin jurusan hanya
                                menyetujui request dan memantau progres stok.
                            </div>
                        )}
                        {consignment.unpaid_amount > 0 && (
                            <Form
                                action={`/admin-jurusan/consignments/${consignment.id}/payout`}
                                method="post"
                                className="mt-5 grid gap-2 border-t border-slate-100 pt-4"
                            >
                                <Input
                                    name="amount"
                                    type="number"
                                    min="1"
                                    max={consignment.unpaid_amount}
                                    placeholder="Nominal cair ke seller"
                                    required
                                />
                                <Input
                                    name="note"
                                    placeholder="Catatan pencairan"
                                />
                                <Button type="submit" variant="outline">
                                    Catat pencairan
                                </Button>
                            </Form>
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}

function RejectConsignmentDialog({
    consignment,
}: {
    consignment: Props['consignment'];
}) {
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <Button type="button" variant="outline">
                    Reject
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Tolak request titip?</AlertDialogTitle>
                    <AlertDialogDescription>
                        {consignment.product.name} dari{' '}
                        {consignment.seller.name} akan ditolak dan status produk
                        seller ikut menjadi rejected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <Form
                    action={`/admin-jurusan/consignments/${consignment.id}/reject`}
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

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs font-medium text-slate-500">{label}</p>
            <p className="mt-1 text-slate-900">{value}</p>
        </div>
    );
}
