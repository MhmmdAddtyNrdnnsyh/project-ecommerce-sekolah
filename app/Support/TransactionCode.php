<?php

namespace App\Support;

use Illuminate\Support\Str;

class TransactionCode
{
    public static function make(): string
    {
        return 'TRX-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
