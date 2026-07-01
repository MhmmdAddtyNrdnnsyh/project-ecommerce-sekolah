import { Form, Head, Link } from '@inertiajs/react';
import { PackagePlus, ShieldCheck, UserPlus, Warehouse } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    upJurusans: {
        id: number;
        name: string;
        description: string | null;
        picket_officers: {
            id: number;
            name: string;
            email: string;
            up_jurusan_id: number | null;
        }[];
    }[];
    picketOptions: {
        id: number;
        name: string;
        email: string;
        up_jurusan_id: number | null;
    }[];
    categories: {
        id: number;
        name: string;
    }[];
};

export default function AdminJurusanUpJurusan({
    upJurusans,
    categories,
}: Props) {
    const hasUpJurusan = upJurusans.length > 0;

    return (
        <>
            <Head title="UP Jurusan" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex items-start gap-4">
                        <span className="grid size-11 shrink-0 place-items-center rounded-[8px] bg-blue-50 text-blue-700">
                            <Warehouse className="size-5" />
                        </span>
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                Master UP
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                UP Jurusan
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Buat unit, assign picket, dan tambah produk
                                milik jurusan.
                            </p>
                        </div>
                    </div>
                </section>

                {hasUpJurusan ? (
                    <div className="flex items-center gap-3 rounded-[8px] border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldCheck className="size-5 shrink-0" />
                        Akun admin jurusan ini sudah memiliki UP Jurusan.
                    </div>
                ) : (
                    <Form
                        action="/admin-jurusan/up-jurusan"
                        method="post"
                        className="grid gap-3 rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_2fr_auto]"
                    >
                        <Input
                            name="name"
                            placeholder="Nama UP Jurusan"
                            required
                        />
                        <Input
                            name="description"
                            placeholder="Deskripsi singkat"
                        />
                        <Button type="submit">Buat UP</Button>
                    </Form>
                )}

                <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    {upJurusans.map((up) => (
                        <UpJurusanItem
                            key={up.id}
                            up={up}
                            categories={categories}
                        />
                    ))}
                    {upJurusans.length === 0 && (
                        <div className="grid place-items-center p-10 text-center">
                            <span className="grid size-12 place-items-center rounded-[8px] bg-slate-100 text-slate-500">
                                <Warehouse className="size-6" />
                            </span>
                            <p className="mt-3 font-medium text-slate-950">
                                Belum ada UP Jurusan.
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                Buat UP pertama untuk mulai kelola produk dan
                                picket.
                            </p>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}

function UpJurusanItem({
    up,
    categories,
}: {
    up: Props['upJurusans'][number];
    categories: Props['categories'];
}) {
    const hasPicket = up.picket_officers.length > 0;

    return (
        <div className="border-b border-slate-100 p-4 last:border-b-0">
            <div className="flex flex-col justify-between gap-3 md:flex-row md:items-start">
                <div>
                    <p className="font-medium text-slate-950">{up.name}</p>
                    <p className="mt-1 text-sm text-slate-500">
                        {up.description ?? '-'}
                    </p>
                </div>
                <span className="w-fit rounded-[6px] bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100">
                    Aktif
                </span>
            </div>
            <div className="mt-4 space-y-3">
                <div>
                    <p className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <UserPlus className="size-4 text-slate-500" />
                        Picket Officer
                    </p>
                    <p className="mt-1 text-sm text-slate-500">
                        {hasPicket
                            ? up.picket_officers
                                  .map(
                                      (picket) =>
                                          `${picket.name} (${picket.email})`,
                                  )
                                  .join(', ')
                            : 'Belum ada picket officer.'}
                    </p>
                </div>

                {!hasPicket && (
                    <div className="rounded-[8px] border border-blue-100 bg-blue-50 p-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-blue-700">
                                Buat akun picket officer dari halaman khusus.
                                Akun baru akan otomatis ditugaskan ke UP ini.
                            </p>
                            <Button
                                asChild
                                className="w-full rounded-[8px] sm:w-auto"
                            >
                                <Link href="/admin-jurusan/picket-officer/create">
                                    <UserPlus className="size-4" />
                                    Buat Picket
                                </Link>
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            <div className="mt-4 border-t border-slate-100 pt-4">
                <div className="mb-3 flex items-center gap-2 text-sm font-medium text-slate-700">
                    <PackagePlus className="size-4 text-slate-500" />
                    Tambah Produk UP
                </div>
                <Form
                    action="/admin-jurusan/products"
                    method="post"
                    className="grid gap-3 md:grid-cols-2"
                >
                    <input
                        type="hidden"
                        name="up_jurusan_id"
                        value={up.id}
                        readOnly
                    />
                    <Input name="name" placeholder="Nama produk UP" required />
                    <Select name="category_id" required>
                        <SelectTrigger className="rounded-[8px] border-slate-200 bg-white">
                            <SelectValue placeholder="Pilih kategori" />
                        </SelectTrigger>
                        <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                            <SelectGroup>
                                <SelectLabel>Kategori</SelectLabel>
                                {categories.map((category) => (
                                    <SelectItem
                                        key={category.id}
                                        value={String(category.id)}
                                    >
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <Input
                        name="description"
                        placeholder="Deskripsi produk"
                        required
                    />
                    <Input
                        name="price"
                        type="number"
                        min="1"
                        placeholder="Harga"
                        required
                    />
                    <Input
                        name="stock"
                        type="number"
                        min="0"
                        placeholder="Stok"
                        required
                    />
                    <Button type="submit">Tambah Produk</Button>
                </Form>
            </div>
        </div>
    );
}
