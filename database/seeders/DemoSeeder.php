<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\Kitchen;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Sample Adyar pilot data so the apps work on first run. Prices and nutrition are placeholders. */
class DemoSeeder extends Seeder
{
    /** One random password for every demo staff account, shown once by the installer. */
    public static ?string $password = null;

    public function run(): void
    {
        self::$password ??= 'Demo-'.\Illuminate\Support\Str::password(10, symbols: false);
        $zone = Zone::firstOrCreate(['slug' => 'adyar'], ['name' => 'Adyar', 'status' => 'live', 'went_live_at' => now(), 'state' => 'Tamil Nadu', 'city' => 'Chennai', 'pincode' => '600020', 'address' => 'Adyar, Chennai', 'center_lat' => 13.0040, 'center_lng' => 80.2580,
            'polygon' => [[13.0175, 80.2420], [13.0180, 80.2720], [12.9925, 80.2745], [12.9890, 80.2440]]]);
        Zone::firstOrCreate(['slug' => 'besant-nagar'], ['name' => 'Besant Nagar', 'status' => 'testing', 'state' => 'Tamil Nadu', 'city' => 'Chennai', 'pincode' => '600090', 'address' => 'Besant Nagar, Chennai', 'center_lat' => 12.9990, 'center_lng' => 80.2700,
            'polygon' => [[13.0050, 80.2620], [13.0060, 80.2760], [12.9930, 80.2770], [12.9925, 80.2640]]]);

        $kitchen = Kitchen::firstOrCreate(['code' => 'ADY'], ['name' => 'Adyar kitchen', 'legal_name' => '[LEGAL NAME]', 'address' => '[KITCHEN ADDRESS], Adyar, Chennai 600020',
            'lat' => 13.0040, 'lng' => 80.2565, 'gstin' => null, 'fssai' => null, 'service_radius_m' => 10000]);
        $kitchen->zones()->syncWithoutDetaching([$zone->id]);

        $cats = collect(['Full meals', 'Rice & bowls', 'Sides & add-ons', 'Drinks'])->mapWithKeys(fn ($n, $i) => [$n => Category::firstOrCreate(['slug' => Str::slug($n)], ['name' => $n, 'sort' => $i])->id]);

        $dishes = [
            ['Vegetable meals', 'Full meals', 'veg', 12000, 520, 16, 82, 12, 9, 'medium', [35, 25, 30, 10], ['milk', 'gluten'], 'Rice, sambar, rasam, poriyal, kootu, curd, appalam'],
            ['Millet meals', 'Full meals', 'veg', 14000, 480, 17, 64, 11, 10, 'low', [30, 25, 35, 10], ['milk'], 'Kodo millet, dal, two vegetables, buttermilk'],
            ['Chettinad chicken meals', 'Full meals', 'nonveg', 16000, 640, 34, 78, 20, 6, 'high', [35, 35, 20, 10], [], 'Rice, chicken curry, rasam, poriyal'],
            ['Millet pongal bowl', 'Rice & bowls', 'veg', 13500, 460, 15, 66, 13, 7, 'medium', [50, 30, 20, 0], ['milk'], 'Foxtail millet, moong dal, coconut chutney'],
            ['Lemon & curd rice', 'Rice & bowls', 'veg', 9500, 430, 10, 76, 10, 4, 'medium', [70, 0, 10, 20], ['milk', 'peanut'], 'Half lemon rice, half curd rice, pickle'],
            ['Sambar rice & poriyal', 'Rice & bowls', 'veg', 9900, 450, 14, 70, 9, 8, 'medium', [45, 30, 25, 0], [], 'Sambar-mixed rice, beans poriyal'],
            ['Bisi bele bath', 'Rice & bowls', 'veg', 11000, 490, 15, 72, 14, 8, 'medium', [45, 30, 25, 0], ['milk'], 'Rice, toor dal, vegetables, ghee'],
            ['Egg curry rice', 'Rice & bowls', 'egg', 13000, 560, 24, 68, 18, 5, 'medium', [45, 35, 15, 5], ['egg'], 'Two eggs in onion-tomato curry, rice'],
            ['Extra curd', 'Sides & add-ons', 'veg', 2000, 60, 3, 5, 3, 0, 'low', null, ['milk'], 'Fresh set curd, 100 g'],
            ['Appalam (2)', 'Sides & add-ons', 'veg', 1500, 90, 3, 10, 4, 1, 'high', null, ['gluten'], 'Crisp papad, 2 pieces'],
            ['Chickpea sundal', 'Sides & add-ons', 'veg', 3500, 140, 7, 20, 4, 6, 'low', null, [], 'Chickpeas, coconut, curry leaf'],
            ['Seasonal fruit cup', 'Sides & add-ons', 'veg', 4500, 90, 1, 21, 0, 3, 'low', null, [], 'Papaya, guava, pomegranate'],
            ['Neer mor', 'Drinks', 'veg', 2500, 45, 2, 4, 2, 0, 'medium', null, ['milk'], 'Spiced buttermilk, ginger, curry leaf'],
            ['Ragi malt', 'Drinks', 'veg', 3500, 120, 4, 22, 2, 3, 'low', null, ['milk'], 'Finger millet, jaggery, milk'],
        ];
        foreach ($dishes as [$name, $cat, $diet, $price, $kcal, $p, $c, $f, $fib, $na, $bal, $alg, $desc]) {
            $dish = Dish::firstOrCreate(['name' => $name], [
                'slug' => Str::slug($name), 'category_id' => $cats[$cat], 'description' => $desc, 'diet' => $diet, 'price_paise' => $price,
                'kcal' => $kcal, 'protein_g' => $p, 'carbs_g' => $c, 'fat_g' => $f, 'fibre_g' => $fib, 'sodium' => $na,
                'balance' => $bal ? array_combine(['grains', 'protein', 'veg', 'dairy'], $bal) : null,
                'ingredients' => array_map('trim', explode(',', $desc)), 'allergens' => $alg,
            ]);
            foreach ([0, 1] as $d) {
                DailyMenu::firstOrCreate(
                    ['kitchen_id' => $kitchen->id, 'dish_id' => $dish->id, 'service_date' => now('Asia/Kolkata')->addDays($d)->toDateString(), 'meal' => 'lunch'],
                    ['price_paise' => $price, 'capacity' => $name === 'Chettinad chicken meals' ? 0 : 60, 'cutoff_at' => '10:45:00'],
                );
            }
        }

        Offer::firstOrCreate(['code' => 'WELCOME50'], ['title' => '₹50 off your first order', 'type' => 'flat', 'value' => 5000, 'min_order_paise' => 15000, 'new_customers_only' => true]);
        Offer::firstOrCreate(['code' => 'MILLET15'], ['title' => '15% off millet meals on Mondays', 'type' => 'percent', 'value' => 15, 'min_order_paise' => 10000, 'max_discount_paise' => 3000, 'days' => [1], 'uses_per_customer' => 4]);

        Banner::firstOrCreate(['placement' => 'home', 'title' => 'Millet Mondays'], ['tag' => 'THIS WEEK', 'subtitle' => '15% off every millet meal', 'cta' => 'See millet meals', 'link' => 'menu', 'sort' => 1,
            'design' => ['layout' => 'split', 'bg' => '#0F5C4D', 'ink' => '#FFFFFF', 'accent' => '#BFE8D6', 'show_code' => true]]);
        Banner::firstOrCreate(['placement' => 'home', 'title' => 'Top up once, eat all week'], ['tag' => 'WALLET', 'subtitle' => 'Lunch paid automatically from your wallet', 'cta' => 'Open wallet', 'link' => 'wallet', 'sort' => 2,
            'design' => ['layout' => 'split', 'bg' => '#FBE3CF', 'ink' => '#3E220C', 'accent' => '#9A4512']]);
        Banner::firstOrCreate(['placement' => 'login', 'title' => 'Home-style lunch, by 1 PM'], ['tag' => 'FRESH EVERY DAY', 'subtitle' => 'Cooked in your neighbourhood kitchen',
            'design' => ['bg' => '#0F5C4D', 'ink' => '#FFFFFF', 'accent' => '#BFE8D6']]);

        // Demo staff (change passwords after install)
        $mk = function (string $role, string $name, string $email, ?string $phone) use ($kitchen) {
            $u = User::firstOrCreate(['email' => $email], ['name' => $name]);
            $u->forceFill(['role_id' => Role::where('slug', $role)->value('id'), 'password' => self::$password, 'phone' => $phone, 'phone_verified_at' => $phone ? now() : null, 'pos_pin' => Hash::make('1234')])->save();
            $u->kitchens()->syncWithoutDetaching([$kitchen->id]);
        };
        $mk('kitchen_manager', 'Lakshmi R.', 'kitchen@dailytable.test', null);
        $mk('cashier', 'Arun P.', 'cashier@dailytable.test', null);
        $mk('rider', 'Murugan K.', 'rider@dailytable.test', '9000000001');
        $this->command?->info('Demo password for all demo staff: '.self::$password);
    }
}
