<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Portal EXIM Database...');
        $this->command->line('');

        $this->call([
            ForwarderSeeder::class,
            UserSeeder::class,
            ExportDataSeeder::class,
        ]);

        $this->command->line('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->line('');
        $this->command->info('🚀 Ready to serve at: php artisan serve');
    }
}