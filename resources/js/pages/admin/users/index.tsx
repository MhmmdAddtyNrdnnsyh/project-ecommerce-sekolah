import { Head, router } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useState } from 'react';
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
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

type AdminUser = {
    id: number;
    name: string;
    email: string;
    role: { code: string; label: string };
    products_count: number;
    orders_count: number;
    created_at: string | null;
};

type Props = {
    users: {
        data: AdminUser[];
        from: number | null;
        to: number | null;
        total: number;
    };
    roles: { code: string; name: string }[];
    filters: { q: string; role: string };
};

const roleStyles: Record<string, string> = {
    admin: 'bg-rose-50 text-rose-700',
    seller: 'bg-emerald-50 text-emerald-700',
    buyer: 'bg-blue-50 text-blue-700',
    picket_officer: 'bg-amber-50 text-amber-700',
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
              new Date(value),
          )
        : '-';

export default function AdminUsersIndex({ users, roles, filters }: Props) {
    const [q, setQ] = useState(filters.q);
    const [role, setRole] = useState(filters.role || '');

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            '/admin/users',
            Object.fromEntries(
                Object.entries({
                    q,
                    role: role === 'all' ? '' : role,
                }).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Admin Users" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <Users className="size-3.5" /> {users.total} user
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Users
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau akun, role, dan aktivitas dasar.
                        </p>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Daftar User</CardTitle>
                            <CardDescription>
                                {users.from ?? 0}-{users.to ?? 0} dari{' '}
                                {users.total} user
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 border-b border-slate-100 p-5 lg:grid-cols-[1fr_12rem_auto]"
                            >
                                <label className="relative">
                                    <span className="sr-only">Cari user</span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={q}
                                        onChange={(event) =>
                                            setQ(event.target.value)
                                        }
                                        placeholder="Nama atau email"
                                        className="rounded-[8px] border-slate-200 bg-white pl-9"
                                    />
                                </label>
                                <Select
                                    value={role}
                                    onValueChange={setRole}
                                >
                                    <SelectTrigger className="w-full rounded-[8px] border-slate-200 bg-white">
                                        <SelectValue placeholder="Pilih role" />
                                    </SelectTrigger>
                                    <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                        <SelectGroup>
                                            <SelectLabel>Role</SelectLabel>
                                            <SelectItem value="all">
                                                Semua role
                                            </SelectItem>
                                            {roles.map((item) => (
                                                <SelectItem
                                                    key={item.code}
                                                    value={item.code}
                                                >
                                                    {item.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <Button type="submit" className="rounded-[8px]">
                                    Terapkan
                                </Button>
                            </form>

                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50">
                                        {[
                                            'User',
                                            'Role',
                                            'Produk',
                                            'Order',
                                            'Daftar',
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
                                    {users.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="py-10 text-center text-slate-500"
                                            >
                                                Tidak ada user.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {users.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="px-5">
                                                <div className="font-medium text-slate-950">
                                                    {user.name}
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    {user.email}
                                                </div>
                                            </TableCell>
                                            <TableCell className="px-5">
                                                <Badge
                                                    className={cn(
                                                        'rounded-[6px]',
                                                        roleStyles[
                                                            user.role.code
                                                        ] ??
                                                            'bg-slate-100 text-slate-700',
                                                    )}
                                                >
                                                    {user.role.label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {user.products_count}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {user.orders_count}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {formatDate(user.created_at)}
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

AdminUsersIndex.layout = {
    breadcrumbs: [{ title: 'Users', href: '/admin/users' }],
};
