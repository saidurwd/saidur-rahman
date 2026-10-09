<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingService $settings): void
    {
        $settings->setMany([
            'site_name' => 'Saidur Rahman',
            'headline' => 'IT leader and builder',
            'positioning' => 'IT leader and builder — 21+ years running enterprise IT, and still shipping production systems (ERP, HMS, AssetTaskPro) myself.',
            'hero_title' => 'I lead IT and I still build the systems.',
            'bio' => 'Head of IT with 21+ years in enterprise IT, ERP and digital transformation.',
            'philosophy' => 'Sample: technology serves the business, people make it work, and simple systems outlast clever ones.',
            'stats' => [['value' => '21+', 'label' => 'Years in enterprise IT']],
            'location' => 'Dhaka, Bangladesh',
            'contact_email' => 'hello@saidur-rahman.com',
            'availability_open' => true,
            'availability_text' => 'Open to senior IT leadership roles in Qatar and the GCC',
        ], 'profile');

        $settings->setMany([
            'linkedin' => null,
            'github' => null,
        ], 'social');

        $settings->setMany([
            'seo_title' => 'Saidur Rahman — Head of IT, ERP and digital transformation',
            'seo_description' => 'IT leader with 21+ years in enterprise IT who still builds production systems. ERP, HMS and asset management case studies.',
        ], 'seo');
    }
}
