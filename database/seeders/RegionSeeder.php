<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    private string $file = 'storage/app/private/wilayah.sql';

    public function run(): void
    {
        $path = base_path($this->file);

        if (!file_exists($path)) {
            $this->command->error("File not found: {$path}");
            return;
        }

        // $this->command->info("Reading SQL file...");

        $batchSize = 1000;
        $data = [];
        $count = 0;

        DB::beginTransaction();

        try {
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new \Exception("Could not open file: {$path}");
            }

            while (($line = fgets($handle)) !== false) {
                // Parse each line for INSERT statements
                if (preg_match_all("/\('([^']*)','([^']*)'\)/", $line, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $code = $match[1];
                        $name = str_replace("''", "'", $match[2]);

                        $data[] = [
                            'code' => $code,
                            'name' => $name,
                            'level' => $this->getLevel($code),
                            'parent_code' => $this->getParentCode($code),
                        ];

                        if (count($data) >= $batchSize) {
                            DB::table('regions')->insert($data);
                            $count += count($data);
                            $data = [];

                            // $this->command->info("Inserted {$count} rows...");
                        }
                    }
                }
            }

            fclose($handle);

            if (!empty($data)) {
                DB::table('regions')->insert($data);
                $count += count($data);
            }

            DB::commit();

            // $this->command->info("✅ Import completed!");
            // $this->command->info("Total rows: {$count}");
        } catch (\Throwable $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            DB::rollBack();

            $this->command->error("❌ Error: " . $e->getMessage());
        }
    }

    /**
     * Hitung level dari kode
     * 11 → 1
     * 11.01 → 2
     * 11.01.01 → 3
     * 11.01.01.2001 → 4
     */
    private function getLevel(string $code): int
    {
        return substr_count($code, '.') + 1;
    }

    /**
     * Ambil parent code
     */
    private function getParentCode(string $code): ?string
    {
        if (!str_contains($code, '.')) {
            return null;
        }

        return substr($code, 0, strrpos($code, '.'));
    }
}
