<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NlbCrawler;

class CrawlNlbLotteries extends Command
{
    protected $signature = 'lottery:crawl';

    protected $description = 'Crawl all NLB lotteries';

    public function handle(NlbCrawler $crawler)
    {
        $start = microtime(true);

        $crawler->crawlAll();

        $time = round(microtime(true)-$start,2);

        $this->info("Finished in {$time}s");
    }
}
