<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\LotteryResult;
use Carbon\Carbon;

class NlbCrawler
{
    protected $baseUrl = "https://www.nlb.lk/results/";

    public function crawlAll()
    {
        foreach (config('lotteries') as $lottery => $slug) {

            $this->crawlLottery($lottery, $slug);
        }
    }

    protected function crawlLottery($lottery, $slug)
    {
        $url = $this->baseUrl . $slug;

        $html = Http::timeout(3)->get($url)->body();

        $crawler = new Crawler($html);

        $crawler->filter('table tbody tr')->each(function ($row) use ($lottery) {

            $drawText = $row->filter('td')->eq(0)->text();

            preg_match('/Draw No\:(\d+)/', $drawText, $match);

            if (!isset($match[1])) return;

            $drawNumber = $match[1];

            // Skip existing (FAST)
            if (LotteryResult::where('lottery', $lottery)
                ->where('draw_number', $drawNumber)
                ->exists()
            ) return;

            $dateText = trim(explode("\n", $drawText)[1]);

            $drawDate = Carbon::parse($dateText);

            $numbers = [];

            $row->filter('td')->eq(1)->filter('li')
                ->each(function ($li) use (&$numbers) {
                    $numbers[] = $li->text();
                });

            $letter = $numbers[0] ?? null;
            $digits = implode('', array_slice($numbers, 1));

            LotteryResult::create([
                'lottery' => $lottery,
                'draw_number' => $drawNumber,
                'draw_date' => $drawDate,
                'letter' => $letter,
                'numbers' => $digits
            ]);
        });
    }
}
