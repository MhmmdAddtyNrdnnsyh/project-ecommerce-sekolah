<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\MajorGroup;
use App\Models\Position;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolReferenceSeeder extends Seeder
{
    /**
     * Seed the school reference data.
     */
    public function run(): void
    {
        foreach ($this->positions() as $position) {
            Position::query()->updateOrCreate(
                ['code' => $position['code']],
                ['name' => $position['name']],
            );
        }

        foreach ($this->majorGroups() as $majorGroup) {
            MajorGroup::query()->updateOrCreate(
                ['code' => $majorGroup['code']],
                ['name' => $majorGroup['name']],
            );
        }

        foreach ($this->majors() as $major) {
            $majorGroup = MajorGroup::query()
                ->where('code', $major['major_group_code'])
                ->firstOrFail();

            $createdMajor = Major::query()->updateOrCreate(
                [
                    'code' => $major['code'],
                    'grade_min' => $major['grade_min'],
                    'grade_max' => $major['grade_max'],
                ],
                [
                    'major_group_id' => $majorGroup->id,
                    'name' => $major['name'],
                ],
            );

            $this->seedClassesForMajor($createdMajor);
        }
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function positions(): array
    {
        return [
            ['code' => Position::TEACHER, 'name' => 'Guru'],
            ['code' => Position::STUDENT, 'name' => 'Siswa'],
        ];
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function majorGroups(): array
    {
        return [
            ['code' => 'software-engineering', 'name' => 'Rekayasa Perangkat Lunak'],
            ['code' => 'network-engineering', 'name' => 'Teknik Jaringan Komputer'],
            ['code' => 'visual-design', 'name' => 'Desain Komunikasi Visual'],
            ['code' => 'business-marketing', 'name' => 'Bisnis dan Pemasaran'],
            ['code' => 'office-management', 'name' => 'Manajemen Perkantoran'],
            ['code' => 'accounting', 'name' => 'Akuntansi'],
        ];
    }

    /**
     * @return array<int, array{major_group_code: string, code: string, name: string, grade_min: int, grade_max: int}>
     */
    private function majors(): array
    {
        return [
            [
                'major_group_code' => 'software-engineering',
                'code' => 'PPLG',
                'name' => 'Pengembangan Perangkat Lunak dan Gim',
                'grade_min' => 10,
                'grade_max' => 10,
            ],
            [
                'major_group_code' => 'software-engineering',
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'grade_min' => 11,
                'grade_max' => 12,
            ],
            [
                'major_group_code' => 'network-engineering',
                'code' => 'TJKT',
                'name' => 'Teknik Jaringan Komputer dan Telekomunikasi',
                'grade_min' => 10,
                'grade_max' => 10,
            ],
            [
                'major_group_code' => 'network-engineering',
                'code' => 'TKJ',
                'name' => 'Teknik Komputer dan Jaringan',
                'grade_min' => 11,
                'grade_max' => 12,
            ],
            [
                'major_group_code' => 'visual-design',
                'code' => 'DKV',
                'name' => 'Desain Komunikasi Visual',
                'grade_min' => 10,
                'grade_max' => 12,
            ],
            [
                'major_group_code' => 'business-marketing',
                'code' => 'PM',
                'name' => 'Pemasaran',
                'grade_min' => 10,
                'grade_max' => 10,
            ],
            [
                'major_group_code' => 'business-marketing',
                'code' => 'BR',
                'name' => 'Bisnis Ritel',
                'grade_min' => 11,
                'grade_max' => 12,
            ],
            [
                'major_group_code' => 'business-marketing',
                'code' => 'BD',
                'name' => 'Bisnis Digital',
                'grade_min' => 11,
                'grade_max' => 12,
            ],
            [
                'major_group_code' => 'office-management',
                'code' => 'MPLB',
                'name' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'grade_min' => 10,
                'grade_max' => 10,
            ],
            [
                'major_group_code' => 'office-management',
                'code' => 'MP',
                'name' => 'Manajemen Perkantoran',
                'grade_min' => 11,
                'grade_max' => 12,
            ],
            [
                'major_group_code' => 'accounting',
                'code' => 'AKL',
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'grade_min' => 10,
                'grade_max' => 10,
            ],
            [
                'major_group_code' => 'accounting',
                'code' => 'AK',
                'name' => 'Akuntansi',
                'grade_min' => 11,
                'grade_max' => 12,
            ],
        ];
    }

    private function seedClassesForMajor(Major $major): void
    {
        foreach (range($major->grade_min, $major->grade_max) as $gradeLevel) {
            SchoolClass::query()->updateOrCreate(
                [
                    'major_id' => $major->id,
                    'grade_level' => $gradeLevel,
                    'section' => 1,
                ],
                ['name' => $this->gradeLabel($gradeLevel).' '.$major->code.' 1'],
            );
        }
    }

    private function gradeLabel(int $gradeLevel): string
    {
        return match ($gradeLevel) {
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            default => (string) $gradeLevel,
        };
    }
}
