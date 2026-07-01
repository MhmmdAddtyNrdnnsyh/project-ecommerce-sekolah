import { Head } from '@inertiajs/react';
import { ClipboardList, ShieldAlert } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export default function PicketUnassigned() {
    return (
        <>
            <Head title="Picket Belum Ditugaskan" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto flex max-w-3xl flex-col gap-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-amber-50 text-amber-700">
                            <ShieldAlert className="size-3.5" />
                            Akses Picket
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Akun belum ditugaskan ke UP Jurusan
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-slate-500">
                            Dashboard picket, POS, penerimaan barang, order,
                            dan laporan baru bisa digunakan setelah akun ini
                            terhubung dengan satu UP Jurusan.
                        </p>
                    </section>

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-6">
                            <div className="flex items-start gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-[8px] bg-amber-50 text-amber-700">
                                    <ClipboardList className="size-5" />
                                </span>
                                <div>
                                    <CardTitle className="text-lg text-slate-950">
                                        Hubungi admin jurusan
                                    </CardTitle>
                                    <CardDescription className="mt-1 text-sm leading-6">
                                        Admin jurusan perlu membuat atau
                                        menghubungkan akun picket ini ke UP
                                        Jurusan yang dikelola. Setelah assigned,
                                        akses picket akan terbuka otomatis.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-3 p-6 text-sm text-slate-600">
                            <div className="rounded-[8px] border border-slate-100 bg-slate-50 p-4">
                                Status akun:{' '}
                                <span className="font-medium text-slate-950">
                                    Menunggu assignment UP Jurusan
                                </span>
                            </div>
                            <p>
                                Jika assignment baru saja dilakukan, keluar lalu
                                masuk kembali atau refresh halaman ini.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
