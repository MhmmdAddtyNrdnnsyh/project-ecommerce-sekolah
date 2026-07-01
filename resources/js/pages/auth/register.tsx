import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BriefcaseBusiness,
    GraduationCap,
    KeyRound,
    Lock,
    Mail,
    School,
    User,
} from 'lucide-react';
import { useState } from 'react';
import type { CSSProperties } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type PositionOption = {
    id: number;
    code: string;
    name: string;
};

type ClassOption = {
    id: number;
    name: string;
    grade_level: number;
    major_code: string;
    major_name: string;
    major_group_name: string;
};

type Props = {
    passwordRules: string;
    positions: PositionOption[];
    classes: ClassOption[];
};

type RegisterTheme = CSSProperties & Record<`--${string}`, string>;

const selectPortalTheme: RegisterTheme = {
    '--foreground': '#0F172A',
    '--popover': '#FFFFFF',
    '--popover-foreground': '#0F172A',
    '--muted-foreground': '#64748B',
    '--accent': '#EFF8FF',
    '--accent-foreground': '#0059B8',
    '--border': '#E2E8F0',
};

const fieldClassName = 'flex flex-col gap-1';
const labelClassName = 'text-xs leading-[1.4] font-medium text-[#334155]';
const iconClassName =
    'pointer-events-none absolute left-3 top-1/2 z-10 size-5 -translate-y-1/2 text-slate-400';
const inputClassName =
    'h-11 rounded-[8px] border-slate-200 bg-white pl-10 pr-4 text-base text-slate-900 shadow-none placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-2 focus-visible:ring-blue-500/20 md:text-base';
const selectTriggerClassName =
    'h-11 w-full rounded-[8px] border-slate-200 bg-white pl-10 pr-4 text-base text-slate-900 shadow-none data-[placeholder]:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-2 focus-visible:ring-blue-500/20 data-[size=default]:h-11 md:text-base';
const errorClassName = 'pt-1 text-xs';

export default function Register({ passwordRules, positions, classes }: Props) {
    const [positionId, setPositionId] = useState('');
    const [gradeLevel, setGradeLevel] = useState('');
    const [classId, setClassId] = useState('');
    const selectedPosition = positions.find(
        (position) => String(position.id) === positionId,
    );
    const isStudent = selectedPosition?.code === 'student';
    const gradeLevels = Array.from(
        new Set(classes.map((schoolClass) => schoolClass.grade_level)),
    ).sort((firstGrade, secondGrade) => firstGrade - secondGrade);
    const filteredClasses = classes.filter(
        (schoolClass) => String(schoolClass.grade_level) === gradeLevel,
    );

    const handlePositionChange = (value: string) => {
        setPositionId(value);

        const nextPosition = positions.find(
            (position) => String(position.id) === value,
        );

        if (nextPosition?.code !== 'student') {
            setGradeLevel('');
            setClassId('');
        }
    };

    const handleGradeLevelChange = (value: string) => {
        setGradeLevel(value);
        setClassId('');
    };

    const gradeLabel = (value: number) => {
        return matchGrade(value);
    };

    return (
        <>
            <Head title="Daftar Sekarang" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex flex-col gap-4">
                            <div className={fieldClassName}>
                                <Label
                                    htmlFor="name"
                                    className={labelClassName}
                                >
                                    Nama Lengkap
                                </Label>
                                <div className="relative">
                                    <User className={iconClassName} />
                                    <Input
                                        id="name"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name="name"
                                        placeholder="Masukkan nama lengkap"
                                        className={inputClassName}
                                        aria-invalid={Boolean(errors.name)}
                                    />
                                </div>
                                <InputError
                                    message={errors.name}
                                    className={errorClassName}
                                />
                            </div>

                            <div className={fieldClassName}>
                                <Label
                                    htmlFor="email"
                                    className={labelClassName}
                                >
                                    Email
                                </Label>
                                <div className="relative">
                                    <Mail className={iconClassName} />
                                    <Input
                                        id="email"
                                        type="email"
                                        required
                                        tabIndex={2}
                                        autoComplete="email"
                                        name="email"
                                        placeholder="contoh@email.com"
                                        className={inputClassName}
                                        aria-invalid={Boolean(errors.email)}
                                    />
                                </div>
                                <InputError
                                    message={errors.email}
                                    className={errorClassName}
                                />
                            </div>

                            <div className={fieldClassName}>
                                <Label
                                    htmlFor="position_id"
                                    className={labelClassName}
                                >
                                    Jabatan
                                </Label>
                                <div className="relative">
                                    <BriefcaseBusiness
                                        className={iconClassName}
                                    />
                                    <Select
                                        name="position_id"
                                        value={positionId}
                                        onValueChange={handlePositionChange}
                                        required
                                    >
                                        <SelectTrigger
                                            id="position_id"
                                            className={selectTriggerClassName}
                                            tabIndex={3}
                                            aria-invalid={Boolean(
                                                errors.position_id,
                                            )}
                                        >
                                            <SelectValue placeholder="Pilih jabatan" />
                                        </SelectTrigger>
                                        <SelectContent
                                            style={selectPortalTheme}
                                        >
                                            <SelectGroup>
                                                <SelectLabel>
                                                    Jabatan
                                                </SelectLabel>
                                                {positions.map((position) => (
                                                    <SelectItem
                                                        key={position.id}
                                                        value={String(
                                                            position.id,
                                                        )}
                                                    >
                                                        {position.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError
                                    message={errors.position_id}
                                    className={errorClassName}
                                />
                            </div>

                            {isStudent && (
                                <>
                                    <div className={fieldClassName}>
                                        <Label
                                            htmlFor="grade_level"
                                            className={labelClassName}
                                        >
                                            Kelas
                                        </Label>
                                        <div className="relative">
                                            <GraduationCap
                                                className={iconClassName}
                                            />
                                            <Select
                                                value={gradeLevel}
                                                onValueChange={
                                                    handleGradeLevelChange
                                                }
                                                required
                                            >
                                                <SelectTrigger
                                                    id="grade_level"
                                                    className={
                                                        selectTriggerClassName
                                                    }
                                                    tabIndex={4}
                                                >
                                                    <SelectValue placeholder="Pilih kelas" />
                                                </SelectTrigger>
                                                <SelectContent
                                                    style={selectPortalTheme}
                                                >
                                                    <SelectGroup>
                                                        <SelectLabel>
                                                            Kelas
                                                        </SelectLabel>
                                                        {gradeLevels.map(
                                                            (level) => (
                                                                <SelectItem
                                                                    key={level}
                                                                    value={String(
                                                                        level,
                                                                    )}
                                                                >
                                                                    {gradeLabel(
                                                                        level,
                                                                    )}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div className={fieldClassName}>
                                        <Label
                                            htmlFor="class_id"
                                            className={labelClassName}
                                        >
                                            Jurusan
                                        </Label>
                                        <div className="relative">
                                            <School className={iconClassName} />
                                            <Select
                                                name="class_id"
                                                value={classId}
                                                onValueChange={setClassId}
                                                disabled={!gradeLevel}
                                                required
                                            >
                                                <SelectTrigger
                                                    id="class_id"
                                                    className={
                                                        selectTriggerClassName
                                                    }
                                                    tabIndex={5}
                                                    aria-invalid={Boolean(
                                                        errors.class_id,
                                                    )}
                                                >
                                                    <SelectValue
                                                        placeholder={
                                                            gradeLevel
                                                                ? 'Pilih jurusan'
                                                                : 'Pilih kelas dulu'
                                                        }
                                                    />
                                                </SelectTrigger>
                                                <SelectContent
                                                    style={selectPortalTheme}
                                                >
                                                    <SelectGroup>
                                                        <SelectLabel>
                                                            Jurusan{' '}
                                                            {gradeLevel
                                                                ? gradeLabel(
                                                                      Number(
                                                                          gradeLevel,
                                                                      ),
                                                                  )
                                                                : ''}
                                                        </SelectLabel>
                                                        {filteredClasses.map(
                                                            (schoolClass) => (
                                                                <SelectItem
                                                                    key={
                                                                        schoolClass.id
                                                                    }
                                                                    value={String(
                                                                        schoolClass.id,
                                                                    )}
                                                                >
                                                                    {
                                                                        schoolClass.major_code
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <InputError
                                            message={errors.class_id}
                                            className={errorClassName}
                                        />
                                    </div>
                                </>
                            )}

                            <div className={fieldClassName}>
                                <Label
                                    htmlFor="password"
                                    className={labelClassName}
                                >
                                    Kata Sandi
                                </Label>
                                <div className="relative">
                                    <Lock className={iconClassName} />
                                    <PasswordInput
                                        id="password"
                                        required
                                        tabIndex={isStudent ? 6 : 4}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="Minimal 8 karakter"
                                        passwordrules={passwordRules}
                                        className={inputClassName}
                                        aria-invalid={Boolean(errors.password)}
                                    />
                                </div>
                                <InputError
                                    message={errors.password}
                                    className={errorClassName}
                                />
                            </div>

                            <div className={fieldClassName}>
                                <Label
                                    htmlFor="password_confirmation"
                                    className={labelClassName}
                                >
                                    Konfirmasi Kata Sandi
                                </Label>
                                <div className="relative">
                                    <KeyRound className={iconClassName} />
                                    <PasswordInput
                                        id="password_confirmation"
                                        required
                                        tabIndex={isStudent ? 7 : 5}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="Ulangi kata sandi"
                                        passwordrules={passwordRules}
                                        className={inputClassName}
                                        aria-invalid={Boolean(
                                            errors.password_confirmation,
                                        )}
                                    />
                                </div>
                                <InputError
                                    message={errors.password_confirmation}
                                    className={errorClassName}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-11 w-full text-base font-semibold shadow-sm transition-colors active:scale-[0.98]"
                                tabIndex={isStudent ? 8 : 6}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Daftar Sekarang
                                <ArrowRight
                                    className="size-4"
                                    data-icon="inline-end"
                                />
                            </Button>
                        </div>

                        <div className="mt-2 text-center text-sm leading-6 text-[#475569]">
                            Sudah punya akun?{' '}
                            <Link
                                href={login()}
                                tabIndex={isStudent ? 9 : 7}
                                className="font-semibold text-blue-700 transition-colors hover:text-blue-800"
                            >
                                Masuk di sini
                            </Link>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Bergabung dengan EduCart',
    description: 'Buat akun buyer untuk mulai belanja di lingkungan sekolah.',
};

function matchGrade(value: number) {
    return matchGradeLabel[value] ?? `Kelas ${value}`;
}

const matchGradeLabel: Record<number, string> = {
    10: 'Kelas X',
    11: 'Kelas XI',
    12: 'Kelas XII',
};
