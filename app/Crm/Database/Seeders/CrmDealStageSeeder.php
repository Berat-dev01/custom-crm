<?php

namespace App\Crm\Database\Seeders;

use App\Crm\Models\DealStage;
use Illuminate\Database\Seeder;

class CrmDealStageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->stages() as $stage) {
            DealStage::query()->updateOrCreate(
                ['slug' => $stage['slug']],
                $stage + ['is_won' => false, 'is_lost' => false]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stages(): array
    {
        return [
            ['name' => 'Yeni', 'slug' => 'new', 'color' => '#64748b', 'position' => 1, 'probability' => 10],
            ['name' => 'Iletisim Kuruldu', 'slug' => 'contacted', 'color' => '#0ea5e9', 'position' => 2, 'probability' => 25],
            ['name' => 'Teklif Hazirlaniyor', 'slug' => 'quote-preparing', 'color' => '#2563eb', 'position' => 3, 'probability' => 45],
            ['name' => 'Teklif Gonderildi', 'slug' => 'quote-sent', 'color' => '#7c3aed', 'position' => 4, 'probability' => 60],
            ['name' => 'Pazarlik', 'slug' => 'negotiation', 'color' => '#f59e0b', 'position' => 5, 'probability' => 75],
            ['name' => 'Kazanildi', 'slug' => 'won', 'color' => '#16a34a', 'position' => 6, 'probability' => 100, 'is_won' => true],
            ['name' => 'Kaybedildi', 'slug' => 'lost', 'color' => '#dc2626', 'position' => 7, 'probability' => 0, 'is_lost' => true],
        ];
    }
}
