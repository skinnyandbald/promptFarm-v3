<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowBoguskyFolders extends Command
{
    protected $signature = 'advisor:show-folders';
    protected $description = 'Show what\'s in each Bogusky folder';

    public function handle()
    {
        $this->info('📁 CURRENT BOGUSKY FOLDER STRUCTURE');
        $this->info('=' . str_repeat('=', 50));
        $this->newLine();
        
        $folders = [
            'V3 Baseline (from v2)' => '/Users/ben/code/promptFarm-v3/storage/app/advisors/baseline-v2/',
            'V3 Original Baseline' => '/Users/ben/code/promptFarm-v3/storage/app/advisors/alex-bogusky-baseline/',
            'V3 Improved (New)' => '/Users/ben/code/promptFarm-v3/storage/app/advisors/alex-bogusky-improved/'
        ];
        
        foreach ($folders as $name => $path) {
            $this->info("🗂️  $name");
            $this->line("   Path: $path");
            
            if (!file_exists($path)) {
                $this->error("   ❌ Folder does not exist");
                $this->newLine();
                continue;
            }
            
            $files = array_diff(scandir($path), ['.', '..', '.DS_Store']);
            
            if (empty($files)) {
                $this->warn("   📭 Empty folder");
            } else {
                foreach ($files as $file) {
                    $fullPath = $path . $file;
                    if (is_dir($fullPath)) {
                        $this->line("   📂 $file/");
                        $subFiles = array_diff(scandir($fullPath), ['.', '..', '.DS_Store']);
                        foreach ($subFiles as $subFile) {
                            $this->line("     📄 $subFile");
                        }
                    } else {
                        $size = number_format(filesize($fullPath));
                        $this->line("   📄 $file ($size bytes)");
                    }
                }
            }
            $this->newLine();
        }
        
        $this->info('🎯 WHAT EACH FOLDER REPRESENTS:');
        $this->line('• V3 Baseline (from v2): Copied from your v2 project main files');
        $this->line('• V3 Original Baseline: Original v3 starter files');
        $this->line('• V3 Improved (New): Generated with our Stage 1 improvements');
        $this->newLine();
        
        $this->info('📊 QUALITY SCORES FROM OUR ANALYSIS:');
        $this->line('• V3 Baseline (from v2): 56.5% overall');
        $this->line('• V3 Original Baseline: 58% overall');
        $this->line('• V3 Improved (New): 66.5% overall ← BEST');
        
        return 0;
    }
}