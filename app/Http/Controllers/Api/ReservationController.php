<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Store;
use App\Services\ReservationMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Reservation / contact form of the public websites (POST /api/reservations/add).
 * Request and response format are kept identical to the old ichiman API.
 */
class ReservationController extends Controller
{
    /** Multi-value form fields, stored as JSON strings (the mail templates json_decode them). */
    private const JSON_FIELDS = ['can_contacted_day', 'can_contacted', 'car'];

    public function store(Request $request, ReservationMailer $mailer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type_reservation' => 'required',
            // The websites send the store slug in store_id.
            'store_id' => 'required|exists:stores,slug',
            'email' => 'required',
            'phone_number' => 'required',
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_error' => true,
                'status_code' => 400,
                'message' => 'Bad Request',
                'data' => $validator->errors(),
            ], 400);
        }

        $store = Store::where('slug', $request->input('store_id'))->firstOrFail();

        $data = $request->only((new Reservation)->getFillable());
        $data['store_id'] = $store->id;

        foreach (self::JSON_FIELDS as $field) {
            if ($request->has($field)) {
                $data[$field] = json_encode($request->input($field));
            }
        }

        if ($request->has('type_question')) {
            $data['type_question'] = json_encode((array) $request->input('type_question'));
        }

        $reservation = Reservation::create($data);

        // The reservation is already saved: a mail failure must not turn into an error page
        // for the customer (the old API answered 500 here). Staff still see it in the admin.
        try {
            $mailer->send($reservation, $store, $request->input('type_raiten', 'raiten'));
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'is_error' => false,
            'status_code' => 200,
            'message' => 'Created',
            'data' => $reservation,
        ]);
    }
}
