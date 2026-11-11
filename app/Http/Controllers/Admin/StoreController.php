<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStoreRequest;
use App\Models\Store;
use App\Support\DataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $stores = DataTable::query(Store::withCount('reservations'), $request,
            sorts: ['name', 'slug', 'brand', 'phone_number', 'reservations_count'],
            defaultSort: ['brand', 'name'],
            filters: [
                AllowedFilter::exact('brand'),
                AllowedFilter::callback('search', fn ($query, $term) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"))),
            ],
        );

        return Inertia::render('Stores/Index', [
            'stores' => DataTable::paginate($stores, $request, 10),
            'query' => DataTable::state($request),
            'brands' => Store::BRANDS,
        ]);
    }

    public function store(SaveStoreRequest $request): RedirectResponse
    {
        Store::create($request->validated());

        return Inertia::flash('success', 'created')->back();
    }

    public function update(SaveStoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return Inertia::flash('success', 'saved')->back();
    }

    public function destroy(Store $store): RedirectResponse
    {
        $store->delete();

        return Inertia::flash('success', 'deleted')->back();
    }
}
