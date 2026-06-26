import { Form, Head } from '@inertiajs/react';
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
    picketOptions,
    categories,
}: Props) {
    const hasUpJurusan = upJurusans.length > 0;

    return (
        <>
            <Head title="UP Jurusan" />
            <main className="space-y-6 p-4 sm:p-6">
                <section>
                    <h1 className="text-2xl font-semibold text-slate-950">
                        UP Jurusan
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Buat dan kelola UP Jurusan.
                    </p>
                </section>

                {hasUpJurusan ? (
                    <div className="rounded-[8px] border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                        Akun admin jurusan ini sudah memiliki UP Jurusan.
                    </div>
                ) : (
                    <Form
                        action="/admin-jurusan/up-jurusan"
                        method="post"
                        className="grid gap-3 rounded-[8px] border border-slate-200 bg-white p-4 md:grid-cols-[1fr_2fr_auto]"
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

                <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    {upJurusans.map((up) => (
                        <div
                            key={up.id}
                            className="border-b border-slate-100 p-4 last:border-b-0"
                        >
                            <p className="font-medium text-slate-950">
                                {up.name}
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                {up.description ?? '-'}
                            </p>
                            <div className="mt-4 grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                                <div>
                                    <p className="text-sm font-medium text-slate-700">
                                        Picket Officer
                                    </p>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {up.picket_officers.length
                                            ? up.picket_officers
                                                  .map(
                                                      (picket) =>
                                                          `${picket.name} (${picket.email})`,
                                                  )
                                                  .join(', ')
                                            : 'Belum ada picket officer.'}
                                    </p>
                                </div>
                                <Form
                                    action={`/admin-jurusan/up-jurusan/${up.id}/assign-picket`}
                                    method="post"
                                    className="flex gap-2"
                                >
                                    <Select name="picket_id" required>
                                        <SelectTrigger className="w-56 rounded-[8px] border-slate-200 bg-white">
                                            <SelectValue placeholder="Pilih picket" />
                                        </SelectTrigger>
                                        <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                            <SelectGroup>
                                                <SelectLabel>
                                                    Picket Officer
                                                </SelectLabel>
                                                {picketOptions.map((picket) => (
                                                    <SelectItem
                                                        key={picket.id}
                                                        value={String(
                                                            picket.id,
                                                        )}
                                                    >
                                                        {picket.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <Button type="submit">Assign</Button>
                                </Form>
                            </div>
                            <Form
                                action="/admin-jurusan/products"
                                method="post"
                                className="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-2"
                            >
                                <input
                                    type="hidden"
                                    name="up_jurusan_id"
                                    value={up.id}
                                    readOnly
                                />
                                <Input
                                    name="name"
                                    placeholder="Nama produk UP"
                                    required
                                />
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
                                <Button type="submit">Tambah Produk UP</Button>
                            </Form>
                        </div>
                    ))}
                </div>
            </main>
        </>
    );
}
