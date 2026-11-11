<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePhotoRequest;
use App\Models\Photo;
use App\Models\Store;
use App\Shared\Uploader;
use App\Support\DataTable;
use App\Support\Sorts\SortByStoreName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/** Customer delivery photos (納車写真) shown on the public websites. */
class PhotoController extends Controller
{
    public function __construct(private Uploader $uploader) {}

    public function index(Request $request): Response
    {
        $photos = DataTable::query(Photo::with('store:id,name'), $request,
            sorts: ['date', 'initial', 'maker', AllowedSort::custom('store', new SortByStoreName)],
            defaultSort: ['-date'],
            tiebreaker: ['-id'], // equal values: newest first
            filters: [
                AllowedFilter::exact('store', 'store_id'),
                ...DataTable::yearMonthFilters('date'),
            ],
        );

        return Inertia::render('Photos/Index', [
            'photos' => DataTable::paginate($photos, $request, 20)->through(fn (Photo $p) => [
                'id' => $p->id,
                'date' => $p->date,
                'initial' => $p->initial,
                'maker' => $p->maker,
                'store_id' => $p->store_id,
                'store' => $p->store?->name,
                'image_url' => $p->imageUrl(),
            ]),
            'query' => DataTable::state($request),
            // Closures: only evaluated on full visits, skipped by the table's partial reloads.
            'stores' => fn () => Store::orderBy('name')->get(['id', 'name']),
            'years' => fn () => DataTable::years(Photo::min('date'), Photo::max('date')),
        ]);
    }

    public function store(SavePhotoRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->uploader->uploadImage($request->file('image'), 500, Photo::IMAGE_DIR);

        Photo::create($data);

        return Inertia::flash('success', 'created')->back();
    }

    public function update(SavePhotoRequest $request, Photo $photo): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->uploader->deleteImage($photo->image, Photo::IMAGE_DIR);
            $data['image'] = $this->uploader->uploadImage($request->file('image'), 500, Photo::IMAGE_DIR);
        }

        $photo->update($data);

        return Inertia::flash('success', 'saved')->back();
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        $photo->delete();

        return Inertia::flash('success', 'deleted')->back();
    }
}
