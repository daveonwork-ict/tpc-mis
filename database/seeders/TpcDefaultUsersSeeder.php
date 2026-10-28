<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One local login for each role. The password is the same default the staff
 * form already stores. Re-running updates the role and permissions and does
 * not replace a faculty or trainer already assigned to a class.
 */
class TpcDefaultUsersSeeder extends Seeder
{
    private const PASSWORD = '123456';

    public function run(): void
    {
        $keywords = $this->keywords();

        $this->role(2, $keywords);
        $this->role(3, [
            'inquiry_read', 'inquiry_create', 'inquiry_update',
            'application_read', 'application_create', 'application_update',
            'requirement_verify', 'screening_create', 'admission_decide', 'admission_setup_read',
            'enrollment_read', 'enrollment_create', 'enrollment_advise', 'enrollment_override', 'enrollment_validate',
            'student_read', 'student_create', 'student_update',
            'parent_read',
            'offering_read',
            'notice_board_read',
        ]);
        $this->role(4, [
            'enrollment_read', 'enrollment_assess', 'enrollment_clear',
            'student_read',
            'notice_board_read',
        ]);
        $this->role(5, [
            'class_read', 'class_update',
            'student_read',
            'notice_board_read',
        ]);

        $this->staffAccount('admin@tpc.tarlac.gov.ph', 'Sample Admin', 'Sample', 'Admin', 2, 3, null, 1, '09173000002', '2026001');
        $this->staffAccount('registrar@tpc.tarlac.gov.ph', 'Sample Registrar', 'Sample', 'Registrar', 3, 8, null, 2, '09173000003', '2026002');
        $this->staffAccount('accounting@tpc.tarlac.gov.ph', 'Sample Accountant', 'Sample', 'Accountant', 4, 6, null, 2, '09173000004', '2026003');
        $faculty = $this->staffAccount('teacher@tpc.tarlac.gov.ph', 'Sample Faculty', 'Sample', 'Faculty', 5, 4, 2, 1, '09173000005', '2026004');

        $openTerm = DB::table('terms')->where('enrollment_open', 1)->value('id');
        if ($openTerm && $faculty) {
            DB::table('offerings')->where('term_id', $openTerm)->whereNull('faculty_id')->update(['faculty_id' => $faculty->id]);
        }
        if ($faculty) {
            DB::table('training_batches')->whereNull('trainer_id')->update(['trainer_id' => $faculty->id]);
        }

        $this->studentAccount();
        $this->guardianAccount();

        $this->command?->info('Default users are ready. Password for each new login is 123456.');
    }

    private function keywords(): array
    {
        $keywords = [];
        foreach (DB::table('permissions')->pluck('keywords') as $json) {
            foreach (json_decode($json, true) ?: [] as $keyword) {
                if (is_string($keyword) && $keyword !== '') {
                    $keywords[] = $keyword;
                }
            }
        }

        return array_values(array_unique($keywords));
    }

    private function role(int $id, array $permissions): void
    {
        $role = Role::find($id);
        if (! $role) {
            return;
        }
        $role->permissions = array_values(array_unique($permissions));
        $role->status = 1;
        $role->save();
    }

    private function staffAccount(
        string $email,
        string $name,
        string $first,
        string $last,
        int $roleId,
        int $designationId,
        ?int $departmentId,
        int $genderId,
        string $phone,
        string $staffNo
    ): ?Staff {
        $role = Role::find($roleId);
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'role_id' => $roleId,
                'permissions' => $role?->permissions ?? [],
                'status' => 1,
                'gender' => $genderId,
                'uuid' => (string) Str::uuid(),
            ]
        );

        $user->name = $name;
        $user->phone = $phone;
        $user->password = Hash::make(self::PASSWORD);
        $user->role_id = $roleId;
        $user->permissions = $role?->permissions ?? [];
        $user->status = 1;
        $user->gender = $genderId;
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();

        $staff = Staff::firstOrCreate(
            ['user_id' => $user->id],
            [
                'staff_id' => $staffNo,
                'role_id' => $roleId,
                'designation_id' => $designationId,
                'department_id' => $departmentId,
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'gender_id' => $genderId,
                'phone' => $phone,
                'joining_date' => '2026-08-01',
                'current_address' => 'Sample office, Tarlac City',
                'permanent_address' => 'Sample residence, Tibag, Tarlac City, Tarlac',
                'status' => 1,
            ]
        );

        $staff->staff_id = $staff->staff_id ?: $staffNo;
        $staff->role_id = $roleId;
        $staff->designation_id = $designationId;
        $staff->department_id = $departmentId;
        $staff->first_name = $first;
        $staff->last_name = $last;
        $staff->email = $email;
        $staff->gender_id = $genderId;
        $staff->phone = $phone;
        $staff->status = 1;
        $staff->save();

        return $staff;
    }

    private function studentAccount(): void
    {
        $student = Student::where('student_code', '2026-00001')->first();
        if (! $student || ! $student->email) {
            return;
        }

        $role = Role::find(6);
        $user = $student->user_id ? User::find($student->user_id) : User::where('email', $student->email)->first();
        if (! $user) {
            $user = new User();
            $user->email = $student->email;
            $user->uuid = (string) Str::uuid();
            $user->email_verified_at = now();
        }

        $user->password = Hash::make(self::PASSWORD);
        $user->name = trim($student->first_name . ' ' . $student->last_name);
        $user->phone = $student->mobile;
        $user->role_id = 6;
        $user->permissions = $role?->permissions ?? [];
        $user->status = 1;
        $user->gender = $student->gender_id ?: 1;
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();

        $student->user_id = $user->id;
        $student->save();
    }

    private function guardianAccount(): void
    {
        $user = User::where('email', 'carlo.mendoza.guardian.sample@tpc.test')->first();
        if (! $user) {
            return;
        }

        $user->password = Hash::make(self::PASSWORD);
        $user->status = 1;
        $user->role_id = 7;
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();
    }
}
