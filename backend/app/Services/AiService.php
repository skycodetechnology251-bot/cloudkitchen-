<?php

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI wrapper. Personal details are stripped before sending; hard food rules
 * (allergies, diet type, sold out) are always re-checked in code after the model answers.
 */
class AiService
{
    public function enabled(): bool
    {
        return (bool) Setting::get('openai', 'api_key', env('OPENAI_API_KEY'));
    }

    public function chat(string $system, string $user, int $maxTokens = 400): ?string
    {
        if (! $this->enabled()) {
            return null;
        }
        $res = Http::withToken(Setting::get('openai', 'api_key', env('OPENAI_API_KEY')))->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => Setting::get('openai', 'model', env('OPENAI_MODEL', 'gpt-4o-mini')),
                'max_tokens' => $maxTokens,
                'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $this->redact($user)]],
            ]);
        if (! $res->successful()) {
            Log::warning('OpenAI error: '.$res->status());
            return null;
        }
        return $res->json('choices.0.message.content');
    }

    /** Customer menu assistant: answers questions about today's menu only. */
    public function menuAnswer(User $user, string $question, int $kitchenId): string
    {
        $menu = DailyMenu::with('dish')->where('kitchen_id', $kitchenId)->whereDate('service_date', today())->get()
            ->map(fn ($m) => "{$m->dish->name} (₹".($m->price_paise / 100).", {$m->dish->kcal} kcal, {$m->dish->diet}, allergens: ".implode(',', $m->dish->allergens ?? []).", left: {$m->available()})")
            ->implode("\n");
        $system = "You are Daily Table's friendly menu helper in Chennai. Answer only about today's menu, ingredients, allergens, prices and ordering. "
            ."Never give medical advice, diagnose, or suggest medicines; suggest asking a doctor for health questions. Keep answers under 80 words.\nToday's menu:\n".$menu;
        return $this->chat($system, $question) ?? 'The assistant is not available right now. You can still browse the full menu.';
    }

    private function redact(string $text): string
    {
        $text = preg_replace('/(\+?91[\s-]?)?[6-9]\d{9}/', '[phone]', $text);
        return preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text);
    }
}
