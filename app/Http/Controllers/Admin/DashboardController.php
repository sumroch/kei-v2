<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Photo;
use App\Models\Reservation;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        // Deferred: the page renders at once, both props arrive in one follow-up request.
        return Inertia::render('Dashboard', [
            'stats' => Inertia::defer(fn () => $this->stats()),
            'latest' => Inertia::defer(fn () => Reservation::select('id', 'name', 'type_reservation', 'store_id', 'created_at')
                ->with('store:id,name')
                ->latest()->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (Reservation $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'type' => $r->type_reservation,
                    'store' => $r->store?->name,
                    'created_at' => $r->created_at?->format('Y-m-d H:i'),
                ])),
        ]);
    }

    private function stats(): array
    {
        $thisMonth = Reservation::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);

        return [
            'reservations_month' => (clone $thisMonth)->count(),
            'reservations_month_by_type' => (clone $thisMonth)->selectRaw('type_reservation, count(*) as total')
                ->groupBy('type_reservation')->pluck('total', 'type_reservation'),
            'reservations_total' => Reservation::count(),
            'cars_active' => Car::where('active', 1)->count(),
            'photos' => Photo::count(),
        ];
    }
}
