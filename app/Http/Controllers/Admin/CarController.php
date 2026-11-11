<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCarRequest;
use App\Models\Car;
use App\Shared\Uploader;
use App\Support\DataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;

class CarController extends Controller
{
    public function __construct(private Uploader $uploader) {}

    public function index(Request $request): Response
    {
        $cars = DataTable::query(Car::query(), $request,
            // price / total / monthly are formatted strings ("1,485,000"): not sortable meaningfully.
            sorts: ['maker', 'brand', 'grade', 'store_type', 'new', 'active', 'created_at'],
            defaultSort: ['store_type', 'maker', 'brand'],
            filters: [
                AllowedFilter::exact('store_type'),
                AllowedFilter::exact('maker'),
                AllowedFilter::callback('active', fn ($query, $value) => $query->where('active', (bool) $value)),
                AllowedFilter::callback('search', fn ($query, $term) => $query->where(fn ($q) => $q
                    ->where('brand', 'like', "%{$term}%")
                    ->orWhere('grade', 'like', "%{$term}%"))),
            ],
        );

        return Inertia::render('Cars/Index', [
            'cars' => DataTable::paginate($cars, $request)->through(fn (Car $car) => [
                ...$car->only(['id', 'store_type', 'brand', 'maker', 'grade', 'fuel', 'price', 'total', 'monthly']),
                'new' => (bool) $car->new,
                'active' => (bool) $car->active,
                'picture_url' => $car->pictureUrl(),
                'created_at' => $car->created_at?->format('Y-m-d'),
            ]),
            'query' => DataTable::state($request),
            'storeTypes' => Car::STORE_TYPES,
            // Closure: only evaluated on full visits, skipped by the table's partial reloads.
            'makers' => fn () => Car::distinct()->orderBy('maker')->pluck('maker'),
        ]);
    }

    public function store(SaveCarRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['picture'] = $this->uploader->uploadImage($request->file('image'), 500, Car::IMAGE_DIR);

        Car::create($this->withDefaults($data));

        return Inertia::flash('success', 'created')->back();
    }

    public function update(SaveCarRequest $request, Car $car): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->uploader->deleteImage($car->picture, Car::IMAGE_DIR);
            $data['picture'] = $this->uploader->uploadImage($request->file('image'), 500, Car::IMAGE_DIR);
        }

        $car->update($this->withDefaults($data));

        return Inertia::flash('success', 'saved')->back();
    }

    public function destroy(Car $car): RedirectResponse
    {
        // Soft delete: the picture file is kept, slides simply stop listing the car.
        $car->delete();

        return Inertia::flash('success', 'deleted')->back();
    }

    /** price / total / monthly are NOT NULL strings with default '0'. */
    private function withDefaults(array $data): array
    {
        foreach (['price', 'total', 'monthly'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                $data[$field] = '0';
            }
        }

        return $data;
    }
}
