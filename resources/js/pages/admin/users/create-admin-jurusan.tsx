import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';
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

export default function CreateAdminJurusan() {
    return (
        <>
            <Head title="Buat Admin Jurusan" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto max-w-3xl space-y-6">
                    <section className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <ShieldCheck className="size-3.5" />
                                Akun pengelola jurusan
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Buat Admin Jurusan
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Akun ini mengelola UP Jurusan dan dapat membuat
                                satu akun picket officer untuk jurusannya.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="w-full rounded-[8px] sm:w-auto"
                        >
                            <Link href="/admin/users">
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Informasi Akun</CardTitle>
                            <CardDescription>
                                Gunakan email sekolah yang aktif agar pemulihan
                                akses lebih mudah.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            <Form
                                action="/admin/users"
                                method="post"
                                className="space-y-5"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="role"
                                            value="admin_jurusan"
                                            readOnly
                                        />
                                        <div className="grid gap-5 sm:grid-cols-2">
                                            <label className="space-y-2">
                                                <span className="text-sm font-medium text-slate-700">
                                                    Nama
                                                </span>
                                                <Input
                                                    name="name"
                                                    placeholder="Nama admin jurusan"
                                                    required
                                                    className="min-h-11 rounded-[8px] border-slate-200 bg-white"
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
                                                    placeholder="admin-jurusan@example.sch.id"
                                                    required
                                                    className="min-h-11 rounded-[8px] border-slate-200 bg-white"
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
                                                    className="min-h-11 rounded-[8px] border-slate-200 bg-white"
                                                />
                                                <InputError
                                                    message={errors.password}
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
                                                    className="min-h-11 rounded-[8px] border-slate-200 bg-white"
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
                                                <Link href="/admin/users">
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
                                                    : 'Buat Akun'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

CreateAdminJurusan.layout = {
    breadcrumbs: [
        { title: 'Users', href: '/admin/users' },
        {
            title: 'Buat Admin Jurusan',
            href: '/admin/users/create-admin-jurusan',
        },
    ],
};
