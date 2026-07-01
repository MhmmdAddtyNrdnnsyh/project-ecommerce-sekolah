import { Head, router, usePage } from '@inertiajs/react';
import { Search, Tags, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

type AdminCategory = {
    id: number;
    name: string;
    slug: string;
    products_count: number;
};

type Props = {
    categories: {
        data: AdminCategory[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { q: string };
};

export default function AdminCategoriesIndex({ categories, filters }: Props) {
    const { flash, errors } = usePage().props;
    const [q, setQ] = useState(filters.q);
    const [name, setName] = useState('');
    const [editing, setEditing] = useState<Record<number, string>>({});

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/admin/categories', q ? { q } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    const createCategory = (event: React.FormEvent) => {
        event.preventDefault();
        router.post(
            '/admin/categories',
            { name },
            {
                preserveScroll: true,
                onSuccess: () => setName(''),
            },
        );
    };

    const updateCategory = (category: AdminCategory) => {
        router.put(
            `/admin/categories/${category.id}`,
            { name: editing[category.id] ?? category.name },
            { preserveScroll: true },
        );
    };

    const deleteCategory = (category: AdminCategory) => {
        router.delete(`/admin/categories/${category.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Admin Kategori" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <Tags className="size-3.5" /> {categories.total}{' '}
                            kategori
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Kategori
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Kelola kategori katalog produk.
                        </p>
                    </section>

                    {(flash.success || flash.error || errors.category) && (
                        <div
                            role="status"
                            className={cn(
                                'rounded-[8px] border px-4 py-3 text-sm',
                                flash.error || errors.category
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            )}
                        >
                            {errors.category || flash.error || flash.success}
                        </div>
                    )}

                    <Card className="rounded-[8px] border-slate-100 shadow-sm">
                        <CardHeader>
                            <CardTitle>Tambah Kategori</CardTitle>
                            <CardDescription>
                                Nama kategori akan dibuat menjadi slug otomatis.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={createCategory}
                                className="grid gap-2 sm:grid-cols-[1fr_auto]"
                            >
                                <div>
                                    <Input
                                        value={name}
                                        onChange={(event) =>
                                            setName(event.target.value)
                                        }
                                        placeholder="Nama kategori"
                                        className="rounded-[8px] border-slate-200 bg-white"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <Button type="submit" className="rounded-[8px]">
                                    Simpan
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Daftar Kategori</CardTitle>
                            <CardDescription>
                                {categories.from ?? 0}-{categories.to ?? 0} dari{' '}
                                {categories.total} kategori
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_auto]"
                            >
                                <label className="relative">
                                    <span className="sr-only">
                                        Cari kategori
                                    </span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={q}
                                        onChange={(event) =>
                                            setQ(event.target.value)
                                        }
                                        placeholder="Cari kategori"
                                        className="rounded-[8px] border-slate-200 bg-white pl-9"
                                    />
                                </label>
                                <Button type="submit" className="rounded-[8px]">
                                    Terapkan
                                </Button>
                            </form>

                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50">
                                        <TableHead className="px-5">
                                            Nama
                                        </TableHead>
                                        <TableHead className="px-5">
                                            Slug
                                        </TableHead>
                                        <TableHead className="px-5">
                                            Produk
                                        </TableHead>
                                        <TableHead className="px-5 text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-10 text-center text-slate-500"
                                            >
                                                Tidak ada kategori.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {categories.data.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell className="px-5">
                                                <Input
                                                    value={
                                                        editing[category.id] ??
                                                        category.name
                                                    }
                                                    onChange={(event) =>
                                                        setEditing({
                                                            ...editing,
                                                            [category.id]:
                                                                event.target
                                                                    .value,
                                                        })
                                                    }
                                                    className="h-9 rounded-[8px] border-slate-200 bg-white"
                                                />
                                            </TableCell>
                                            <TableCell className="px-5 text-slate-500">
                                                {category.slug}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {category.products_count}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        className="rounded-[8px]"
                                                        onClick={() =>
                                                            updateCategory(
                                                                category,
                                                            )
                                                        }
                                                    >
                                                        Update
                                                    </Button>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger
                                                            asChild
                                                        >
                                                            <Button
                                                                type="button"
                                                                size="icon"
                                                                variant="outline"
                                                                className="rounded-[8px] border-rose-200 text-rose-700 hover:bg-rose-50"
                                                                aria-label={`Hapus ${category.name}`}
                                                            >
                                                                <Trash2 className="size-4" />
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>
                                                                    Hapus
                                                                    kategori?
                                                                </AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    Kategori "
                                                                    {
                                                                        category.name
                                                                    }
                                                                    " akan
                                                                    dihapus
                                                                    permanen
                                                                    jika belum
                                                                    memiliki
                                                                    produk.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel
                                                                    asChild
                                                                >
                                                                    <Button
                                                                        type="button"
                                                                        variant="outline"
                                                                        className="rounded-[8px]"
                                                                    >
                                                                        Batal
                                                                    </Button>
                                                                </AlertDialogCancel>
                                                                <Button
                                                                    type="button"
                                                                    className="rounded-[8px] bg-rose-600 text-white hover:bg-rose-700"
                                                                    onClick={() =>
                                                                        deleteCategory(
                                                                            category,
                                                                        )
                                                                    }
                                                                >
                                                                    Hapus
                                                                </Button>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

AdminCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Kategori', href: '/admin/categories' }],
};
