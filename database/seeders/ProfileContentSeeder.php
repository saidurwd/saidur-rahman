<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\SkillGroup;
use Illuminate\Database\Seeder;

/** Sample content only. Replace with real entries from the admin panel. */
class ProfileContentSeeder extends Seeder
{
    public function run(): void
    {
        Experience::query()->create([
            'company' => 'Sample Company (current)',
            'role' => 'Head of IT',
            'location' => 'Dhaka, Bangladesh',
            'employment_type' => 'Full-time',
            'start_date' => '2018-01-01',
            'is_current' => true,
            'summary' => 'Sample: leads IT strategy, infrastructure, security and in-house systems.',
            'achievements' => ['Sample achievement one.', 'Sample achievement two.'],
        ]);
        Experience::query()->create([
            'company' => 'Sample Company (previous)',
            'role' => 'IT Manager',
            'location' => 'Dhaka, Bangladesh',
            'employment_type' => 'Full-time',
            'start_date' => '2008-01-01',
            'end_date' => '2017-12-31',
            'summary' => 'Sample: ran enterprise IT operations and ERP rollouts.',
            'achievements' => ['Sample achievement one.'],
            'sort_order' => 1,
        ]);

        Education::query()->create([
            'institution' => 'Sample University',
            'degree' => 'BSc',
            'field' => 'Computer Science',
            'start_year' => 2000,
            'end_year' => 2004,
        ]);

        Certification::query()->create([
            'name' => 'Sample Certification',
            'issuer' => 'Sample Issuer',
            'issued_at' => '2020-06-01',
        ]);

        $groups = [
            'IT leadership & governance' => ['IT strategy', 'Vendor management', 'Budgeting'],
            'Enterprise systems' => ['ERP', 'Hospital management systems', 'Asset management'],
            'Engineering' => ['Laravel', 'PHP', 'MySQL'],
            'Infrastructure & security' => ['Networking', 'Backup & recovery', 'Access control'],
        ];

        $order = 0;
        foreach ($groups as $name => $skills) {
            $group = SkillGroup::query()->create(['name' => $name, 'sort_order' => $order++]);
            foreach ($skills as $i => $skill) {
                Skill::query()->create([
                    'skill_group_id' => $group->id,
                    'name' => $skill,
                    'level' => 4,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
