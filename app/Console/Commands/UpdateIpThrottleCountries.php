<?php

namespace App\Console\Commands;

use App\Models\IpThrottle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Torann\GeoIP\Facades\GeoIP;

class UpdateIpThrottleCountries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-ip-throttle-countries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update country field for existing IpThrottle records with null country';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $throttles = IpThrottle::whereNull('country')->get();

        if ($throttles->isEmpty()) {
            $this->info('No IpThrottle records with null country found.');
            return;
        }

        $this->info("Found {$throttles->count()} IpThrottle records with null country. Updating...");

        $bar = $this->output->createProgressBar($throttles->count());
        $bar->start();

        $updated = 0;
        foreach ($throttles as $throttle) {
            try {
                $country = $this->getCountryFromIP($throttle->ip_address);
                if ($country) {
                    $throttle->country = $country;
                    $throttle->save();
                    $updated++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to update country for IP {$throttle->ip_address}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Updated {$updated} IpThrottle records with country information.");
    }

    /**
     * Get country name from IP address using GeoIP
     *
     * @param string $ip
     * @return string|null
     */
    private function getCountryFromIP(string $ip): ?string
    {
        try {
            $location = GeoIP::getLocation($ip);
            return $location['country'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
