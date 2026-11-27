<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function ask(Request $r, AiService $ai)
    {
        $d = $r->validate(['question' => 'required|string|max:500', 'kitchen_id' => 'required|exists:kitchens,id']);
        return response()->json(['answer' => $ai->menuAnswer($r->user(), $d['question'], (int) $d['kitchen_id'])]);
    }
}
