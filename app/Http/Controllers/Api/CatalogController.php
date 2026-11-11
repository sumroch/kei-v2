<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Photo;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;

/**
 * Read-only data for the public websites (flat7 / joycal / keigayasui / gyauto).
 * The JSON envelope {is_error, status_code, message, data} matches the old ichiman API,
 * so the websites keep working without changes.
 */
class CatalogController extends Controller
{
    public function slidesKeigayasui(): JsonResponse
    {
        return $this->success($this->activeSlides('keigayasui', ['id', 'title1', 'maker', 'owl', 'dots']));
    }

    public function slidesFlat7Joycal(): JsonResponse
    {
        return $this->success($this->activeSlides('flat7_joycal', ['id', 'title1', 'title2', 'owl', 'color']));
    }

    public function carsKeigayasui(): JsonResponse
    {
        return $this->success($this->activeCars('keigayasui'));
    }

    public function carsFlat7Joycal(): JsonResponse
    {
        return $this->success($this->activeCars('flat7_joycal'));
    }

    public function photos(): JsonResponse
    {
        return $this->success($this->photoQuery()->get());
    }

    /** Photos for the gyauto site: every brand except keigayasui. */
    public function photosGyauto(): JsonResponse
    {
        return $this->success($this->photoQuery()->where('stores.brand', '!=', 'keigayasui')->get());
    }

    private function activeSlides(string $storeType, array $columns)
    {
        return Slide::select($columns)
            ->where('store_type', $storeType)
            ->where('active', 1)
            ->with(['car' => fn ($query) => $query->orderBy('pivot_order_number')])
            ->orderBy('order_slide')
            ->get();
    }

    private function activeCars(string $storeType)
    {
        return Car::select('id', 'brand', 'grade')
            ->where('active', 1)
            ->where('store_type', $storeType)
            ->get();
    }

    private function photoQuery()
    {
        return Photo::select('photos.id', 'photos.initial', 'stores.name_id', 'photos.date', 'photos.image', 'photos.maker', 'stores.name as store', 'photos.store_id')
            ->join('stores', 'photos.store_id', '=', 'stores.id')
            ->orderBy('photos.date');
    }

    private function success(mixed $data): JsonResponse
    {
        return response()->json([
            'is_error' => false,
            'status_code' => 200,
            'message' => 'Success',
            'data' => $data,
        ]);
    }
}
