<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Store;
use App\Support\DataTable;
use App\Support\Sorts\SortByStoreName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    public const TYPES = ['contact', 'raiten', 'shinsa'];

    /** Columns the list needs; the ~20 form fields are only loaded for the detail modal. */
    private const LIST_COLUMNS = ['id', 'name', 'how_to_read', 'email', 'phone_number', 'type_reservation', 'store_id', 'created_at'];

    public function index(Request $request): Response
    {
        $reservations = DataTable::query(Reservation::select(self::LIST_COLUMNS)->with('store:id,name'), $request,
            sorts: ['created_at', 'name', 'email', 'type_reservation', AllowedSort::custom('store', new SortByStoreName)],
            defaultSort: ['-created_at'],
            tiebreaker: ['-id'], // equal values: newest first
            filters: [
                AllowedFilter::exact('type', 'type_reservation'),
                AllowedFilter::exact('store', 'store_id'),
                ...DataTable::yearMonthFilters('created_at'),
                AllowedFilter::callback('search', fn ($query, $term) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('how_to_read', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone_number', 'like', "%{$term}%"))),
            ],
        );

        return Inertia::render('Reservations/Index', [
            'reservations' => DataTable::paginate($reservations, $request)->through(fn (Reservation $r) => [
                ...$r->only(['id', 'name', 'how_to_read', 'email', 'phone_number', 'type_reservation']),
                'store' => $r->store?->name,
                'created_at' => $r->created_at?->format('Y-m-d H:i'),
            ]),
            'query' => DataTable::state($request),
            // Only when the detail modal asks for it: router.reload({ only: ['detail'], data: { detail: id } }).
            'detail' => Inertia::optional(fn () => $this->detail($request->integer('detail'))),
            // Closures: only evaluated on full visits, skipped by the table's partial reloads.
            'stores' => fn () => Store::orderBy('name')->get(['id', 'name']),
            'years' => fn () => DataTable::years(Reservation::min('created_at'), Reservation::max('created_at')),
            'types' => self::TYPES,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'type' => ['nullable', 'in:'.implode(',', self::TYPES)],
            'store' => ['nullable', 'integer'],
        ]);

        $rows = Reservation::query()
            ->whereBetween('created_at', [$filters['date_start'].' 00:00:00', $filters['date_end'].' 23:59:59'])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type_reservation', $type))
            ->when($filters['store'] ?? null, fn ($q, $store) => $q->where('store_id', $store))
            ->with('store:id,name')
            ->oldest()
            ->get();

        $storeName = isset($filters['store']) ? Store::find($filters['store'])?->slug : 'all';
        $fileName = "reservations_{$storeName}_{$filters['date_start']}_{$filters['date_end']}.csv";

        // EUC-JP like the old export, so existing spreadsheets/macros keep working.
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            $put = fn (array $row) => fputcsv($out, array_map(fn ($v) => mb_convert_encoding((string) $v, 'EUC-JP', 'UTF-8'), $row));

            $put(['No', 'Name', 'Email', 'Address', 'Phone Number', 'Store', 'Date Book', 'Type']);
            foreach ($rows as $i => $r) {
                $put([$i + 1, $r->name, $r->email, $r->address ?? '-', $r->phone_number, $r->store?->name, $r->created_at, $r->type_reservation]);
            }

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=EUC-JP']);
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->delete();

        return Inertia::flash('success', 'deleted')->back();
    }

    /** Everything the customer filled in, for the detail modal. */
    private function detail(int $id): ?array
    {
        $r = Reservation::with('store:id,name')->find($id);

        if (! $r) {
            return null;
        }

        return [
            ...$r->only([
                'id', 'name', 'how_to_read', 'email', 'phone_number', 'address', 'type_reservation',
                'type_customer', 'usage', 'buyer', 'other_car', 'question', 'other_question', 'message',
            ]),
            // "date1 time1" ... preferred visit dates. The old forms stored "-" for empty fields.
            'preferred' => collect([1, 2, 3])
                ->map(fn ($n) => collect([$r->{"date{$n}"}, $r->{"time{$n}"}])->reject(fn ($v) => trim((string) $v, " -") === '')->implode(' '))
                ->filter()->values()->all(),
            // Stored as JSON strings by the public form.
            'can_contacted' => $this->decodeList($r->can_contacted),
            'can_contacted_day' => $this->decodeList($r->can_contacted_day),
            'type_question' => $this->decodeList($r->type_question),
            'car' => $this->decodeList($r->car),
            'store' => $r->store?->name,
            'created_at' => $r->created_at?->format('Y-m-d H:i'),
        ];
    }

    /** @return list<string> */
    private function decodeList(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [$value];
    }
}
