<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryResult extends Model
{
    protected $table = 'lottery_results';

    protected $fillable = [
        'lottery',
        'draw_number',
        'draw_date',
        'letter',
        'numbers'
    ];

    protected $casts = [
        'draw_date' => 'date',
    ];
}
