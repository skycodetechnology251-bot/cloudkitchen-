<?php

namespace App\Http\Controllers\Admin;

use App\Models\Expense;
use App\Models\Kitchen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExpenseController extends CrudController
{
    protected string $model = Expense::class;
    protected string $title = 'Purchases & expenses';
    protected string $route = 'admin.expenses';
    protected array $columns = ['description' => 'What', 'category' => 'Category', 'amount_paise' => 'Amount', 'paid_paise' => 'Paid', 'spent_on' => 'Date'];

    protected function query()
    {
        return auth()->user()->limitToBranches(Expense::latest('spent_on'));
    }

    protected function fields(?Model $item): array
    {
        $cats = ['ingredients' => 'Vegetables & groceries', 'supplies' => 'Cooking supplies', 'packaging' => 'Packaging', 'salary' => 'Salary', 'rent' => 'Rent', 'gas' => 'Gas & power', 'delivery' => 'Delivery partners', 'marketing' => 'Marketing & offers', 'other' => 'Other'];
        return [
            'kitchen_id' => ['label' => 'Branch', 'type' => 'select', 'options' => auth()->user()->accessibleKitchens()->pluck('name', 'id')->all(), 'rules' => ['required', 'integer', \Illuminate\Validation\Rule::in(auth()->user()->accessibleKitchens()->pluck('id')->all())]],
            'category' => ['label' => 'Category', 'type' => 'select', 'options' => $cats, 'rules' => 'required|in:'.implode(',', array_keys($cats))],
            'description' => ['label' => 'Description', 'rules' => 'required|string|max:160'],
            'supplier' => ['label' => 'Supplier / payee', 'rules' => 'nullable|string|max:120'],
            'quantity' => ['label' => 'Quantity', 'type' => 'number', 'rules' => 'nullable|numeric|min:0'],
            'unit' => ['label' => 'Unit (kg, l, pcs)', 'rules' => 'nullable|string|max:10'],
            'amount_paise' => ['label' => 'Bill amount (₹)', 'type' => 'money', 'rules' => 'required|numeric|min:0'],
            'paid_paise' => ['label' => 'Paid so far (₹)', 'type' => 'money', 'rules' => 'nullable|numeric|min:0'],
            'spent_on' => ['label' => 'Date', 'type' => 'date', 'rules' => 'required|date'],
            'bill' => ['label' => 'Bill photo', 'type' => 'file', 'column' => 'bill_path', 'dir' => 'bills', 'disk' => 'local', 'rules' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $data['recorded_by'] = $r->user()->id;
        $item->forceFill($data)->save();
    }
}
