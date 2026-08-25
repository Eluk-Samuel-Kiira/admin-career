<?php

namespace App\Console\Commands;

use App\Services\Indexing\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate 
                            {--country= : Generate sitemap for specific country code (AU, UG, KE, etc.)}
                            {--all : Generate sitemaps for all countries}
                            {--ping : Ping search engines after generation}
                            {--upload : Upload sitemaps to frontend servers}';
    
    protected $description = 'Generate XML sitemaps for each country frontend';

    public function handle(SitemapService $sitemapService): int
    {
        $this->info('🚀 Starting sitemap generation...');
        $startTime = microtime(true);

        try {
            if ($this->option('country')) {
                $country = strtoupper($this->option('country'));
                $this->info("📁 Generating sitemap for country: {$country}");
                $result = $sitemapService->generateCountrySitemap($country);
                
                if (isset($result['error'])) {
                    $this->error('❌ ' . $result['error']);
                    return 1;
                }
                
                $this->info("✅ Generated " . $result['total_urls'] . " URLs for {$result['country']}");
                $this->line("   Frontend: {$result['frontend_url']}");
                $this->line("   Sitemap Index: {$result['sitemap_index']}");
                
                if ($this->option('ping')) {
                    $this->info('📡 Pinging search engines...');
                    $pingResults = $sitemapService->pingSearchEngines($country);
                    
                    foreach ($pingResults as $engine => $result) {
                        $status = $result['success'] ? '✅' : '❌';
                        $this->line("  {$status} {$engine}: " . ($result['success'] ? 'Success' : ($result['error'] ?? 'Failed')));
                    }
                }
            } else {
                $this->info('📁 Generating sitemaps for all countries...');
                $results = $sitemapService->generateAll();
                
                $rows = [];
                foreach ($results as $code => $result) {
                    if (isset($result['error'])) {
                        $rows[] = [$code, '❌', $result['error']];
                    } else {
                        $rows[] = [$code, '✅', $result['total_urls'] . ' URLs'];
                    }
                }
                
                $this->table(['Country', 'Status', 'Details'], $rows);
            }

            // Show stats
            $stats = $sitemapService->getStats();
            $this->newLine();
            $this->info('📊 Sitemap Statistics:');
            $this->line("  Total active jobs: {$stats['total_jobs']}");
            $this->line("  Legacy jobs: {$stats['legacy_jobs']}");
            $this->line("  New jobs: {$stats['new_jobs']}");
            
            if (isset($stats['countries'])) {
                $this->newLine();
                $this->info('🌍 Country Breakdown:');
                foreach ($stats['countries'] as $code => $country) {
                    $status = $country['sitemap_exists'] ? '✅' : '❌';
                    $this->line("  {$status} {$country['name']} ({$code}): {$country['jobs']} jobs");
                }
            }

            $elapsed = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("✅ Sitemap generation completed in {$elapsed}s");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error generating sitemap: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}