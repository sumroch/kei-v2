<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSlideRequest;
use App\Models\Car;
use App\Models\Slide;
use App\Support\DataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;

/** Car carousels ("slides") of the public websites, each with an ordered list of cars. */
class SlideController extends Controller
{
    public function index(Request $request): Response
    {
        $slides = DataTable::query(Slide::with(['car' => fn ($q) => $q->select('cars.id', 'brand', 'grade')->orderBy('pivot_order_number')]), $request,
            sorts: ['store_type', 'order_slide', 'title1', 'active'],
            defaultSort: ['store_type', 'order_slide'],
            filters: [
                AllowedFilter::exact('store_type'),
                AllowedFilter::callback('active', fn ($query, $value) => $query->where('active', (bool) $value)),
            ],
            // equal values keep the carousel order
            tiebreaker: ['order_slide', 'id'],
        );

        return Inertia::render('Slides/Index', [
            'slides' => DataTable::paginate($slides, $request)->through(fn (Slide $s) => [
                ...$s->only(['id', 'store_type', 'title1', 'title2', 'maker', 'color', 'owl', 'order_slide']),
                'active' => (bool) $s->active,
                'cars' => $s->car->map(fn (Car $c) => ['id' => $c->id, 'brand' => $c->brand, 'grade' => $c->grade]),
            ]),
            'query' => DataTable::state($request),
            'storeTypes' => Car::STORE_TYPES,
            // Car picker data (with thumbnails). Closure: full visits only, skipped by table reloads.
            'cars' => fn () => Car::orderBy('maker')->orderBy('brand')
                ->get(['id', 'store_type', 'brand', 'grade', 'maker', 'picture', 'active'])
                ->map(fn (Car $c) => [
                    ...$c->only(['id', 'store_type', 'brand', 'grade', 'maker']),
                    'active' => (bool) $c->active,
                    'picture_url' => $c->pictureUrl(),
                ]),
        ]);
    }

    public function store(SaveSlideRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $slide = Slide::create([...$this->attributes($data), ...$this->carouselIds($data)]);
            $this->syncCars($slide, $data['cars'] ?? []);
        });

        return Inertia::flash('success', 'created')->back();
    }

    public function update(SaveSlideRequest $request, Slide $slide): RedirectResponse
    {
        $data = $request->validated();
        $attributes = $this->attributes($data);

        // keigayasui title1 is baked into the carousel id (owl), so it isn't changed afterwards.
        if ($slide->store_type === 'keigayasui') {
            unset($attributes['title1']);
        }

        DB::transaction(function () use ($slide, $attributes, $data) {
            $slide->update($attributes);
            $this->syncCars($slide, $data['cars'] ?? []);
        });

        return Inertia::flash('success', 'saved')->back();
    }

    public function destroy(Slide $slide): RedirectResponse
    {
        DB::transaction(function () use ($slide) {
            $slide->car()->sync([]);
            $slide->delete();
        });

        return Inertia::flash('success', 'deleted')->back();
    }

    private function attributes(array $data): array
    {
        $common = [
            'store_type' => $data['store_type'],
            'order_slide' => $data['order_slide'] ?? null,
            'active' => $data['active'],
        ];

        return $data['store_type'] === 'keigayasui'
            ? [...$common, 'title1' => strtoupper($data['title1']), 'maker' => $data['maker']]
            : [...$common, 'title1' => $data['title1'], 'title2' => $data['title2'] ?? null, 'color' => isset($data['color']) ? strtoupper($data['color']) : null];
    }

    /** Carousel element ids used by the websites' markup (same scheme as the old API). */
    private function carouselIds(array $data): array
    {
        if ($data['store_type'] !== 'keigayasui') {
            return ['owl' => 'owl-'.strtoupper(uniqid())];
        }

        $base = 'owl-'.strtolower($data['title1']);
        $taken = Slide::withTrashed()->where('owl', $base)->exists();

        return [
            'owl' => $taken ? $base.'-'.uniqid() : $base,
            'dots' => $taken ? $base.'-'.uniqid() : $base,
        ];
    }

    /** @param list<int> $carIds in display order */
    private function syncCars(Slide $slide, array $carIds): void
    {
        $slide->car()->sync(
            collect($carIds)->values()->mapWithKeys(fn ($id, $position) => [$id => ['order_number' => $position]])->all()
        );
    }
}
