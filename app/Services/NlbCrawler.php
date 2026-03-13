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

    // protected function crawlLottery($lottery, $slug)
    // {
    //     $url = $this->baseUrl . $slug;

    //     $html = Http::timeout(3)->get($url)->body();

    //     $crawler = new Crawler($html);

    //     $crawler->filter('table tbody tr')->each(function ($row) use ($lottery) {

    //         $drawText = $row->filter('td')->eq(0)->text();

    //         preg_match('/Draw No\:(\d+)/', $drawText, $match);

    //         if (!isset($match[1])) return;

    //         $drawNumber = $match[1];

    //         // Skip existing (FAST)
    //         if (LotteryResult::where('lottery', $lottery)
    //             ->where('draw_number', $drawNumber)
    //             ->exists()
    //         ) return;

    //         $dateText = trim(explode("\n", $drawText)[1]);

    //         $drawDate = Carbon::parse($dateText);

    //         $numbers = [];

    //         $row->filter('td')->eq(1)->filter('li')
    //             ->each(function ($li) use (&$numbers) {
    //                 $numbers[] = $li->text();
    //             });

    //         $letter = $numbers[0] ?? null;
    //         $digits = implode('', array_slice($numbers, 1));

    //         LotteryResult::create([
    //             'lottery' => $lottery,
    //             'draw_number' => $drawNumber,
    //             'draw_date' => $drawDate,
    //             'letter' => $letter,
    //             'numbers' => $digits
    //         ]);
    //     });
    // }

    // protected function crawlLottery($lottery, $slug)
    // {
    //     $url = $this->baseUrl . $slug;

    //     $html = Http::timeout(5)->get($url)->body();

    //     $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

    //     $rows = $crawler->filter('table.tbl tbody tr');

    //     $rows->each(function ($row) use ($lottery) {

    //         if ($row->filter('td')->count() < 2) {
    //             return;
    //         }

    //         $drawText = $row->filter('td')->eq(0)->text();

    //         preg_match('/Draw\s*No\:\s*(\d+)/i', $drawText, $match);

    //         if (!isset($match[1])) {
    //             return;
    //         }

    //         $drawNumber = $match[1];

    //         // Skip existing
    //         if (\App\Models\LotteryResult::where('lottery', $lottery)
    //             ->where('draw_number', $drawNumber)
    //             ->exists()
    //         ) {
    //             return;
    //         }

    //         $numbers = [];

    //         $row->filter('td')->eq(1)->filter('li')->each(function ($li) use (&$numbers) {
    //             $numbers[] = trim($li->text());
    //         });

    //         dd($drawNumber, $numbers);

    //         if (count($numbers) < 2) {
    //             return;
    //         }

    //         $letter = $numbers[0];
    //         $digits = implode('', array_slice($numbers, 1));

    //         \App\Models\LotteryResult::create([
    //             'lottery' => $lottery,
    //             'draw_number' => $drawNumber,
    //             'draw_date' => now(), // NLB date parsing sometimes fails
    //             'letter' => $letter,
    //             'numbers' => $digits
    //         ]);
    //     });
    // }

    protected function crawlLottery($lottery, $slug)
    {

        $url = $this->baseUrl . $slug;

        // $html = Http::withHeaders([
        //     'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        //     'Accept' => 'text/html,application/xhtml+xml',
        //     'Accept-Language' => 'en-US,en;q=0.9',
        // ])->timeout(10)->get($url)->body();

        $html = file_get_contents(public_path('mahajana.html'));
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $crawler->filter('table.tbl tbody tr')->each(function ($row) use ($lottery) {

            if ($row->filter('td')->count() < 2) {
                return;
            }

            // draw number
            $drawNumber = $row->filter('td')->eq(0)->filter('b')->text();

            // skip existing
            if (\App\Models\LotteryResult::where('lottery', $lottery)
                ->where('draw_number', $drawNumber)
                ->exists()
            ) {
                return;
            }

            // date
            $dateText = trim(str_replace($drawNumber, '', $row->filter('td')->eq(0)->text()));
            $drawDate = \Carbon\Carbon::parse($dateText);

            // numbers
            $numbers = [];

            $row->filter('td')->eq(1)->filter('li')->each(function ($li) use (&$numbers) {
                $numbers[] = trim($li->text());
            });

            if (count($numbers) < 7) {
                return;
            }

            $letter = $numbers[0];
            $digits = implode('', array_slice($numbers, 1));

            \App\Models\LotteryResult::create([
                'lottery' => $lottery,
                'draw_number' => $drawNumber,
                'draw_date' => $drawDate,
                'letter' => $letter,
                'numbers' => $digits
            ]);
        });
    }
}
