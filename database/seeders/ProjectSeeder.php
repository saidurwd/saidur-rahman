<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Seeder;

/** Placeholder case studies. Replace with real, defensible content. */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            ['HMS ERP', 'Hospital management and ERP system.', ['Laravel', 'MySQL', 'Redis']],
            ['Tea ERP', 'ERP for a tea estate: production, inventory and finance.', ['Laravel', 'MySQL']],
            ['AssetTaskPro', 'Asset and task management product.', ['Laravel', 'Alpine.js', 'MySQL']],
        ];

        foreach ($projects as $i => [$title, $summary, $stack]) {
            Project::query()->create([
                'title' => $title,
                'summary' => $summary,
                'role' => 'Lead architect and developer',
                'period' => 'Sample period',
                'problem' => '<p>Sample: the problem this system solved.</p>',
                'solution' => '<p>Sample: what was built and why.</p>',
                'architecture' => '<p>Sample: architecture overview.</p>',
                'outcomes' => '<p>Sample: outcomes. Add only figures you can defend.</p>',
                'tech_stack' => $stack,
                'is_featured' => true,
                'status' => ProjectStatus::Published,
                'sort_order' => $i,
            ]);
        }
    }
}
