import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock3, Store, XCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type SellerApplication = {
    id: number;
    store_name: string;
    phone: string;
    product_plan: string;
    reason: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    created_at: string | null;
    reviewed_at: string | null;
};

type Props = {
    application: SellerApplication | null;
};

const statusMeta = {
    pending: {
        label: 'Menunggu review',
        icon: Clock3,
        className: 'bg-amber-50 text-amber-700',
    },
    approved: {
        label: 'Disetujui',
        icon: CheckCircle2,
        className: 'bg-emerald-50 text-emerald-700',
    },
    rejected: {
        label: 'Ditolak',
        icon: XCircle,
        className: 'bg-rose-50 text-rose-700',
    },
};

export default function SellerApplicationIndex({ application }: Props) {
    const canApply = !application || application.status === 'rejected';
    const meta = application ? statusMeta[application.status] : null;
    const StatusIcon = meta?.icon;

    return (
        <>
            <Head title="Ajukan Jadi Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                    <section className="space-y-3">
                        <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                            <Store className="size-3.5" /> Seller EduCart
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Ajukan Jadi Seller
                        </h1>
                        <p className="text-sm leading-6 text-slate-500">
                            Akun buyer tetap aktif. Setelah admin menyetujui
                            pengajuan, akun kamu otomatis berubah menjadi seller
                            dan bisa mengelola produk.
                        </p>
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-[8px]"
                        >
                            <Link href="/settings/profile">
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>

                        {application && meta && StatusIcon && (
                            <Card className="rounded-[8px] border-slate-100 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <StatusIcon className="size-5" />
                                        Status Pengajuan
                                    </CardTitle>
                                    <CardDescription>
                                        Pengajuan terakhir untuk{' '}
                                        {application.store_name}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm text-slate-600">
                                    <Badge
                                        className={`rounded-[6px] ${meta.className}`}
                                    >
                                        {meta.label}
                                    </Badge>
                                    {application.rejection_reason && (
                                        <p>
                                            Alasan ditolak:{' '}
                                            {application.rejection_reason}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </section>

                    <Card className="rounded-[8px] border-slate-100 shadow-sm">
                        <CardHeader>
                            <CardTitle>Data Pengajuan</CardTitle>
                            <CardDescription>
                                Isi data toko dan rencana produk yang akan
                                dijual.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {canApply ? (
                                <Form
                                    action="/seller-application"
                                    method="post"
                                    className="space-y-4"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="space-y-1.5">
                                                <Label htmlFor="store_name">
                                                    Nama Toko
                                                </Label>
                                                <Input
                                                    id="store_name"
                                                    name="store_name"
                                                    required
                                                    maxLength={100}
                                                    placeholder="Contoh: Toko ATK XI RPL"
                                                />
                                                <InputError
                                                    message={errors.store_name}
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="phone">
                                                    Nomor WhatsApp
                                                </Label>
                                                <Input
                                                    id="phone"
                                                    name="phone"
                                                    required
                                                    maxLength={30}
                                                    placeholder="08xxxxxxxxxx"
                                                />
                                                <InputError
                                                    message={errors.phone}
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="product_plan">
                                                    Produk yang Akan Dijual
                                                </Label>
                                                <Textarea
                                                    id="product_plan"
                                                    name="product_plan"
                                                    required
                                                    maxLength={1000}
                                                    placeholder="Tulis jenis produk, contoh: makanan ringan, alat tulis, karya jurusan."
                                                />
                                                <InputError
                                                    message={
                                                        errors.product_plan
                                                    }
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="reason">
                                                    Catatan Tambahan
                                                </Label>
                                                <Textarea
                                                    id="reason"
                                                    name="reason"
                                                    maxLength={1000}
                                                    placeholder="Opsional"
                                                />
                                                <InputError
                                                    message={errors.reason}
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="rounded-[8px]"
                                            >
                                                Kirim Pengajuan
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <p className="text-sm text-slate-500">
                                    Pengajuan kamu sedang diproses. Tunggu admin
                                    menyelesaikan review.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
