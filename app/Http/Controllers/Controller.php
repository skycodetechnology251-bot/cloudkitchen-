<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function paise(int $p): string
    {
        return number_format($p / 100, 2, '.', '');
    }
}
