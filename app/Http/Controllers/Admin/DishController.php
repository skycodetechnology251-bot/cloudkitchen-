<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Dish;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DishController extends CrudController
{
    protected string $model = Dish::class;
    protected string $title = 'Dishes';
    protected string $route = 'admin.dishes';
    protected array $columns = ['name' => 'Dish', 'diet' => 'Diet', 'price_paise' => 'Price', 'kcal' => 'kcal', 'reviewed_at' => 'Reviewed'];

    protected function fields(?Model $item): array
    {
        $allergens = ['milk', 'gluten', 'peanut', 'tree nuts', 'sesame', 'soy', 'egg', 'shellfish', 'fish', 'mustard'];
        $conds = ['Diabetes', 'High blood pressure', 'High cholesterol', 'Thyroid', 'PCOS', 'Heart care', 'Weight management'];
        return [
            'name' => ['label' => 'Name', 'rules' => 'required|string|max:80'],
            'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => Category::orderBy('sort')->pluck('name', 'id')->all(), 'rules' => 'required|exists:categories,id'],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:300'],
            'diet' => ['label' => 'Diet', 'type' => 'select', 'options' => ['veg' => 'Vegetarian', 'egg' => 'Contains egg', 'nonveg' => 'Non-vegetarian'], 'rules' => 'required|in:veg,egg,nonveg'],
            'price_paise' => ['label' => 'Default price (₹)', 'type' => 'money', 'rules' => 'required|numeric|min:1'],
            'image' => ['label' => 'Top-down photo (square, 1080 px)', 'type' => 'file', 'column' => 'image_path', 'dir' => 'dishes', 'rules' => 'nullable|image|max:1024'],
            'kcal' => ['label' => 'Calories (kcal)', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
            'protein_g' => ['label' => 'Protein (g)', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
            'carbs_g' => ['label' => 'Carbs (g)', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
            'fat_g' => ['label' => 'Fat (g)', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
            'fibre_g' => ['label' => 'Fibre (g)', 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
            'sodium' => ['label' => 'Sodium', 'type' => 'select', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'], 'rules' => 'required|in:low,medium,high'],
            'balance' => ['label' => 'Plate balance % (grains, protein, veg, dairy)', 'type' => 'json', 'rules' => 'nullable|json', 'help' => 'Example: {"grains":35,"protein":25,"veg":30,"dairy":10}'],
            'ingredients' => ['label' => 'Ingredients', 'type' => 'json', 'rules' => 'nullable|json', 'help' => 'Example: ["Ponni rice","Sambar","Curd"]'],
            'allergens' => ['label' => 'Declared allergens', 'type' => 'multiselect', 'options' => array_combine($allergens, $allergens), 'rules' => 'nullable|array'],
            'suitable_for' => ['label' => 'Reviewed as suitable for (dietitian only)', 'type' => 'multiselect', 'options' => array_combine($conds, $conds), 'rules' => 'nullable|array', 'perm' => 'menu.nutrition'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $item->slug ??= Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $nutritionChanged = $item->exists && collect(['kcal', 'protein_g', 'carbs_g', 'fat_g', 'allergens', 'ingredients', 'suitable_for'])->contains(fn ($k) => array_key_exists($k, $data) && $item->{$k} != $data[$k]);
        if (! $r->user()->hasPermission('menu.nutrition')) {
            unset($data['suitable_for']);
            if ($nutritionChanged || ! $item->exists) {
                $data['reviewed_at'] = null;   // recipe change invalidates the review
                $data['reviewed_by'] = null;
            }
        } else {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = $r->user()->id;
        }
        $item->forceFill($data)->save();
    }
}
