<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Department;
use App\Models\Subsidiary;
use App\Mail\WelcomeUserMail;

/**
 * Assigns department_head role and department(s) to known heads.
 * Safe to re-run — uses updateOrCreate / sync throughout.
 *
 * Run: php artisan db:seed --class=DeptHeadsSeeder
 */
class DeptHeadsSeeder extends Seeder
{
    public function run(): void
    {
        // Pre-load lookups
        $deptByCode = Department::pluck('id', 'code');
        $subByCode  = Subsidiary::pluck('id', 'code');

        /*
         * Format per entry:
         *   email         => string
         *   dept_code     => primary department code (or null for subsidiary users)
         *   sub_code      => primary subsidiary code (or null for dept users)
         *   extra_depts   => array of additional dept codes for approval scope
         */
        $defaultPassword = 'Goil@' . now()->year;

        $heads = [
            // ── Single-department heads ───────────────────────────────────────
            ['name' => 'Denis Amui',            'email' => 'denis.amui@goil.com.gh',          'dept_code' => '02001', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Nanaama Kusi-Appouh',   'email' => 'nanaama.kusi-appouh@goil.com.gh',  'dept_code' => '01004', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Joshua K. Duodu',       'email' => 'joshua.kduodu@goil.com.gh',        'dept_code' => '03001', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Amentor Aziakar',       'email' => 'amentor.aziakar@goil.com.gh',      'dept_code' => '16000', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Millicent Ocquaye',     'email' => 'millicent.ocquaye@goil.com.gh',    'dept_code' => '07000', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Robert Kyere',          'email' => 'robert.kyere@goil.com.gh',         'dept_code' => '08001', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Kingsley Ansah',        'email' => 'kingsley.ansah@goil.com.gh',       'dept_code' => '18000', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Emmanuel Agyiri',       'email' => 'emmanuel.agyiri@goil.com.gh',      'dept_code' => '04001', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Gloria Otoo',           'email' => 'gloria.otoo@goil.com.gh',          'dept_code' => '01002', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Marian Fordjor',        'email' => 'marian.fordjor@goil.com.gh',       'dept_code' => '01003', 'sub_code' => null,  'extra_depts' => []],
            ['name' => 'Edward Bawa',           'email' => 'edward.bawa@goil.com.gh',          'dept_code' => '01001', 'sub_code' => null,  'extra_depts' => []],

            // ── Multi-department heads ────────────────────────────────────────
            ['name' => 'Marcus Dake',           'email' => 'marcus.dake@goil.com.gh',          'dept_code' => '15000', 'sub_code' => null,  'extra_depts' => ['17000']],
            ['name' => 'Mark Ofori-Amanfo',     'email' => 'mark.ofori-amanfo@goil.com.gh',    'dept_code' => '01007', 'sub_code' => null,  'extra_depts' => ['01006', '01008']],

            // ── Subsidiary heads ─────────────────────────────────────────────
            ['name' => 'Theophilus Otchere',    'email' => 'theophilus.otchere@goenery.com.gh','dept_code' => null,    'sub_code' => 'GEL', 'extra_depts' => []],

            // anyeyure@goil.com.gh (GOBITUMEN) — entity not yet in system
            ['name' => '',                       'email' => 'anyeyure@goil.com.gh',             'dept_code' => null,    'sub_code' => null,  'extra_depts' => [],
             '_note' => 'GOBITUMEN not found — create the department/subsidiary first then re-run'],
        ];

        $ok = 0;

        foreach ($heads as $row) {
            $email = strtolower(trim($row['email']));

            // Flag entries that need manual setup
            if (isset($row['_note'])) {
                $this->command->warn("  SKIP  {$email} — {$row['_note']}");
                continue;
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                $deptId = $row['dept_code'] ? ($deptByCode[$row['dept_code']] ?? null) : null;
                $subId  = $row['sub_code']  ? ($subByCode[$row['sub_code']]   ?? null) : null;

                $user = User::create([
                    'name'                 => $row['name'],
                    'email'                => $email,
                    'password'             => bcrypt($defaultPassword),
                    'must_change_password' => true,
                    'is_active'            => true,
                    'department_id'        => $subId ? null : $deptId,
                    'subsidiary_id'        => $subId,
                ]);

                try {
                    Mail::to($user->email)->send(new WelcomeUserMail(
                        $user,
                        $defaultPassword,
                        url('/login')
                    ));
                    $this->command->info("  NEW   {$user->name} ({$email}) — created & welcome email sent");
                } catch (\Exception $e) {
                    $this->command->warn("  NEW   {$user->name} ({$email}) — created but email failed: " . $e->getMessage());
                }
            }

            // Resolve dept / subsidiary IDs
            $deptId = $row['dept_code'] ? ($deptByCode[$row['dept_code']] ?? null) : null;
            $subId  = $row['sub_code']  ? ($subByCode[$row['sub_code']]   ?? null) : null;

            if ($row['dept_code'] && !$deptId) {
                $this->command->warn("  WARN  {$email} — dept code {$row['dept_code']} not found");
            }
            if ($row['sub_code'] && !$subId) {
                $this->command->warn("  WARN  {$email} — subsidiary code {$row['sub_code']} not found");
            }

            // Update user record
            $user->update([
                'department_id' => $subId ? null : $deptId,
                'subsidiary_id' => $subId,
            ]);

            // Ensure department_head role
            if (!$user->hasRole('department_head')) {
                $user->assignRole('department_head');
            }

            // Sync extra approval departments
            $extraIds = collect($row['extra_depts'])
                ->map(fn($code) => $deptByCode[$code] ?? null)
                ->filter()
                ->values()
                ->toArray();

            $user->approvalDepartments()->sync($extraIds);

            $label = $deptId
                ? Department::find($deptId)?->name
                : (Subsidiary::find($subId)?->name ?? '?');

            $extras = $row['extra_depts'] ? ' + extras: ' . implode(', ', $row['extra_depts']) : '';
            $this->command->info("  OK    {$user->name} ({$email}) → {$label}{$extras}");
            $ok++;
        }

        $this->command->newLine();
        $this->command->info("  {$ok} head(s) updated.");

    }
}
