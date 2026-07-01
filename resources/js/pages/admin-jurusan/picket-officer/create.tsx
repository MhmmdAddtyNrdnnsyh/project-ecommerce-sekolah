import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck, UserPlus, Warehouse } from 'lucide-react';
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

type UpJurusan = {
    id: number;
    name: string;
    description: string | null;
    picket_officers: {
        id: number;
        name: string;
        email: string;
    }[];
};

type Props = {
    upJurusan: UpJurusan | null;
};

export default function CreatePicketOfficer({ upJurusan }: Props) {
    const hasPicket = Boolean(upJurusan?.picket_officers.length);

    return (
        <>
            <Head title="Buat Picket Officer" />
            <main className="min-h-dvh bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto max-w-3xl space-y-6">
                    <section className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <UserPlus className="size-3.5" />
                                Auto assign UP Jurusan
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Buat Akun Picket Officer
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Akun picket yang dibuat di sini otomatis
                                terhubung ke UP Jurusan milik admin jurusan ini.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="w-full rounded-[8px] sm:w-auto"
                        >
                            <Link href="/admin-jurusan/up-jurusan">
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle className="flex items-center gap-2">
                                <Warehouse className="size-5 text-blue-700" />
                                UP Tujuan
                            </CardTitle>
                            <CardDescription>
                                Picket officer hanya boleh mengelola satu UP
                                Jurusan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            {upJurusan ? (
                                <div className="rounded-[8px] border border-slate-100 bg-slate-50 p-4">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p className="font-medium text-slate-950">
                                                {upJurusan.name}
                                            </p>
                                            <p className="mt-1 text-sm text-slate-500">
                                                {upJurusan.description ?? '-'}
                                            </p>
                                        </div>
                                        <Badge className="w-fit rounded-[6px] bg-emerald-50 text-emerald-700">
                                            Aktif
                                        </Badge>
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-[8px] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    Buat UP Jurusan terlebih dahulu sebelum
                                    membuat akun picket officer.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {hasPicket && upJurusan && (
                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="border-b border-slate-100 p-5">
                                <CardTitle className="flex items-center gap-2">
                                    <ShieldCheck className="size-5 text-emerald-700" />
                                    Picket Sudah Ada
                                </CardTitle>
                                <CardDescription>
                                    Satu UP Jurusan hanya memiliki satu akun
                                    picket officer.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5">
                                {upJurusan.picket_officers.map((picket) => (
                                    <div
                                        key={picket.id}
                                        className="rounded-[8px] border border-slate-100 p-4"
                                    >
                                        <p className="font-medium text-slate-950">
                                            {picket.name}
                                        </p>
                                        <p className="mt-1 text-sm text-slate-500">
                                            {picket.email}
                                        </p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {upJurusan && !hasPicket && (
                        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                            <CardHeader className="border-b border-slate-100 p-5">
                                <CardTitle>Informasi Akun</CardTitle>
                                <CardDescription>
                                    Password bisa diganti oleh picket setelah
                                    login.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5">
                                <Form
                                    action={`/admin-jurusan/up-jurusan/${upJurusan.id}/pickets`}
                                    method="post"
                                    className="space-y-5"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-5 sm:grid-cols-2">
                                                <label className="space-y-2">
                                                    <span className="text-sm font-medium text-slate-700">
                                                        Nama
                                                    </span>
                                                    <Input
                                                        name="name"
                                                        placeholder="Nama picket"
                                                        required
                                                        className="min-h-11 rounded-[8px]"
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                        className="text-xs"
                                                    />
                                                </label>
                                                <label className="space-y-2">
                                                    <span className="text-sm font-medium text-slate-700">
                                                        Email
                                                    </span>
                                                    <Input
                                                        name="email"
                                                        type="email"
                                                        placeholder="picket@example.sch.id"
                                                        required
                                                        className="min-h-11 rounded-[8px]"
                                                    />
                                                    <InputError
                                                        message={errors.email}
                                                        className="text-xs"
                                                    />
                                                </label>
                                                <label className="space-y-2">
                                                    <span className="text-sm font-medium text-slate-700">
                                                        Password
                                                    </span>
                                                    <Input
                                                        name="password"
                                                        type="password"
                                                        placeholder="Password"
                                                        required
                                                        className="min-h-11 rounded-[8px]"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.password
                                                        }
                                                        className="text-xs"
                                                    />
                                                </label>
                                                <label className="space-y-2">
                                                    <span className="text-sm font-medium text-slate-700">
                                                        Konfirmasi Password
                                                    </span>
                                                    <Input
                                                        name="password_confirmation"
                                                        type="password"
                                                        placeholder="Ulangi password"
                                                        required
                                                        className="min-h-11 rounded-[8px]"
                                                    />
                                                </label>
                                            </div>

                                            <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                                                <Button
                                                    asChild
                                                    type="button"
                                                    variant="outline"
                                                    className="rounded-[8px]"
                                                >
                                                    <Link href="/admin-jurusan/up-jurusan">
                                                        Batal
                                                    </Link>
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    className="rounded-[8px]"
                                                >
                                                    {processing
                                                        ? 'Membuat...'
                                                        : 'Buat & Assign'}
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </main>
        </>
    );
}

CreatePicketOfficer.layout = {
    breadcrumbs: [
        { title: 'UP Jurusan', href: '/admin-jurusan/up-jurusan' },
        {
            title: 'Buat Picket Officer',
            href: '/admin-jurusan/picket-officer/create',
        },
    ],
};
