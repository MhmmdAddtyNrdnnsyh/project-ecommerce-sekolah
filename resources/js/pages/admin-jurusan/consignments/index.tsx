import { Form, Head, Link } from '@inertiajs/react';
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
    return (
        <>
            <Head title="Request Titip Barang" />
            <main className="space-y-6 p-4 sm:p-6">
                <section>
                    <h1 className="text-2xl font-semibold text-slate-950">
                        Request Titip Barang
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Approve atau tolak request seller.
                    </p>
                </section>

                <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    {consignments.map((item) => (
                        <div
                            key={item.id}
                            className="grid gap-3 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-[1fr_1fr_1fr_auto] md:items-center"
                        >
                            <div>
                                <p className="font-medium text-slate-950">
                                    {item.product_name}
                                </p>
                                <p className="text-slate-500">
                                    {item.seller_name}
                                </p>
                            </div>
                            <span>{item.up_jurusan_name}</span>
                            <span>
                                {item.requested_quantity} item -{' '}
                                {item.status.label}
                            </span>
                            <div className="flex gap-2">
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={`/admin-jurusan/consignments/${item.id}`}
                                    >
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
                    Reject
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Tolak request titip?</AlertDialogTitle>
                    <AlertDialogDescription>
                        {item.product_name} dari {item.seller_name} akan
                        ditolak dan status produk seller ikut menjadi rejected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel asChild>
                        <Button type="button" variant="outline">
                            Batal
                        </Button>
                    </AlertDialogCancel>
                    <Form
                        action={`/admin-jurusan/consignments/${item.id}/reject`}
                        method="post"
                    >
                        <Button type="submit" variant="destructive">
                            Reject
                        </Button>
                    </Form>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
