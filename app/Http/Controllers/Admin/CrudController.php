<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Shared list/create/edit/delete for simple admin records. Child classes describe
 * fields once; views/admin/crud/*.blade.php render them.
 * Field types: text, textarea, number, money (stored in paise), select, multiselect,
 * checkbox, date, datetime, json, file, password.
 */
abstract class CrudController extends Controller
{
    protected string $model;
    protected string $title;
    protected string $route;           // e.g. admin.zones
    protected array $columns = [];     // field => label
    protected bool $canDelete = true;

    abstract protected function fields(?Model $item): array;

    protected function query()
    {
        return $this->model::query()->latest('id');
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $item->forceFill($data)->save();
    }

    public function index(Request $r)
    {
        $q = $this->query();
        if ($s = $r->input('q')) {
            $first = array_key_first($this->columns);
            $q->where($first, 'like', '%'.$s.'%');
        }
        return view('admin.crud.index', ['items' => $q->paginate(25)->withQueryString(), 'columns' => $this->columns, 'title' => $this->title, 'route' => $this->route, 'canDelete' => $this->canDelete]);
    }

    public function create()
    {
        return view('admin.crud.form', ['item' => new $this->model, 'fields' => $this->fields(null), 'title' => 'New '.Str::singular($this->title), 'route' => $this->route]);
    }

    public function edit($id)
    {
        $item = $this->query()->findOrFail($id);
        return view('admin.crud.form', ['item' => $item, 'fields' => $this->fields($item), 'title' => 'Edit '.Str::singular($this->title), 'route' => $this->route]);
    }

    public function store(Request $r)
    {
        $item = new $this->model;
        $this->filled($item, $this->validated($r, null), $r);
        AuditService::log($this->route.'.create', $item);
        return redirect()->route($this->route.'.index')->with('status', Str::singular($this->title).' created.');
    }

    public function update(Request $r, $id)
    {
        $item = $this->query()->findOrFail($id);
        $this->filled($item, $this->validated($r, $item), $r);
        AuditService::log($this->route.'.update', $item);
        return redirect()->route($this->route.'.index')->with('status', 'Saved.');
    }

    public function destroy($id)
    {
        abort_unless($this->canDelete, 403);
        $item = $this->query()->findOrFail($id);
        AuditService::log($this->route.'.delete', $item);
        $item->delete();
        return back()->with('status', 'Deleted.');
    }

    protected function validated(Request $r, ?Model $item): array
    {
        $fields = $this->fields($item);
        $rules = collect($fields)->mapWithKeys(fn ($f, $k) => [$k => $f['rules'] ?? 'nullable'])->all();
        $data = $r->validate($rules);
        foreach ($fields as $k => $f) {
            $type = $f['type'] ?? 'text';
            if ($type === 'checkbox') {
                $data[$k] = $r->boolean($k);
            } elseif ($type === 'money' && isset($data[$k])) {
                $data[$k] = (int) round(((float) $data[$k]) * 100);
            } elseif ($type === 'json' && isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = json_decode($data[$k], true);
            } elseif ($type === 'file') {
                unset($data[$k]);
                if ($r->hasFile($k)) {
                    $col = $f['column'] ?? $k;
                    // Photos: Google Drive when connected, else public/uploads. Private files (bills) stay private.
                    $data[$col] = ($f['disk'] ?? 'public') === 'local'
                        ? $r->file($k)->store($f['dir'] ?? 'uploads', 'local')
                        : \App\Support\Media::store($r->file($k), $f['dir'] ?? 'uploads', $item?->{$col});
                }
            } elseif ($type === 'password') {
                if (empty($data[$k])) {
                    unset($data[$k]);
                } else {
                    $data[$k] = bcrypt($data[$k]);
                }
            } elseif ($type === 'multiselect') {
                $data[$k] = array_values(array_filter((array) ($data[$k] ?? [])));
            }
        }
        return $data;
    }
}
