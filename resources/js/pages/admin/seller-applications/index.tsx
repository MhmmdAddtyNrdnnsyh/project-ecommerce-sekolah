import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, Store, XCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type SellerApplication = {
    id: number;
    store_name: string;
    phone: string;
    product_plan: string;
    reason: string | null;
    user: { name: string; email: string };
    created_at: string | null;
};

type Props = {
    sellerApplications: SellerApplication[];
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : '-';

export default function AdminSellerApplicationsIndex({
    sellerApplications,
}: Props) {
    return (
        <>
            <Head title="Pengajuan Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-emerald-50 text-emerald-700">
                            <Store className="size-3.5" />{' '}
                            {sellerApplications.length} pengajuan
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Pengajuan Seller
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Review buyer yang ingin membuka toko di EduCart.
                        </p>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle className="flex items-center gap-2">
                                <Store className="size-5 text-emerald-700" />
                                Daftar Pengajuan
                            </CardTitle>
                            <CardDescription>
                                Setujui buyer yang datanya sudah siap, atau
                                tolak jika belum sesuai.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-slate-50">
                                            {[
                                                'Pemohon',
                                                'Toko',
                                                'Rencana Produk',
                                                'Tanggal',
                                                'Aksi',
                                            ].map((heading) => (
                                                <TableHead
                                                    key={heading}
                                                    className="px-5"
                                                >
                                                    {heading}
                                                </TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {sellerApplications.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="py-10 text-center text-slate-500"
                                                >
                                                    Tidak ada pengajuan seller.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {sellerApplications.map(
                                            (application) => (
                                                <TableRow key={application.id}>
                                                    <TableCell className="px-5">
                                                        <div className="font-medium text-slate-950">
                                                            {
                                                                application.user
                                                                    .name
                                                            }
                                                        </div>
                                                        <div className="text-xs text-slate-500">
                                                            {
                                                                application.user
                                                                    .email
                                                            }
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="px-5">
                                                        <div className="font-medium text-slate-950">
                                                            {
                                                                application.store_name
                                                            }
                                                        </div>
                                                        <div className="text-xs text-slate-500">
                                                            {application.phone}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="max-w-sm px-5 text-sm whitespace-normal text-slate-600">
                                                        {
                                                            application.product_plan
                                                        }
                                                        {application.reason && (
                                                            <p className="mt-1 text-xs text-slate-500">
                                                                Catatan:{' '}
                                                                {
                                                                    application.reason
                                                                }
                                                            </p>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="px-5">
                                                        {formatDate(
                                                            application.created_at,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="px-5">
                                                        <div className="flex flex-wrap gap-2">
                                                            <Form
                                                                action={`/admin/seller-applications/${application.id}/approve`}
                                                                method="post"
                                                            >
                                                                {({
                                                                    processing,
                                                                }) => (
                                                                    <Button
                                                                        type="submit"
                                                                        size="sm"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                        className="rounded-[8px] bg-emerald-600 hover:bg-emerald-700"
                                                                    >
                                                                        <CheckCircle2 className="size-4" />
                                                                        Approve
                                                                    </Button>
                                                                )}
                                                            </Form>
                                                            <Form
                                                                action={`/admin/seller-applications/${application.id}/reject`}
                                                                method="post"
                                                            >
                                                                {({
                                                                    processing,
                                                                }) => (
                                                                    <Button
                                                                        type="submit"
                                                                        size="sm"
                                                                        variant="outline"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                        className="rounded-[8px] border-rose-200 text-rose-700 hover:bg-rose-50"
                                                                    >
                                                                        <XCircle className="size-4" />
                                                                        Reject
                                                                    </Button>
                                                                )}
                                                            </Form>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

AdminSellerApplicationsIndex.layout = {
    breadcrumbs: [
        { title: 'Pengajuan Seller', href: '/admin/seller-applications' },
    ],
};
