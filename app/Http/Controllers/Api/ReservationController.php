<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Display a listing of reservations
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with(['table']);

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('reservation_date', $request->date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by table
        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        $reservations = $query->orderBy('reservation_date')
                            ->orderBy('reservation_time')
                            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Reservations',
            'data' => $reservations
        ]);
    }

    /**
     * Store a newly created reservation
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'party_size' => 'required|integer|min:1',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
            'special_requests' => 'nullable|string'
        ]);

        // Check table availability
        $reservationDateTime = Carbon::parse($request->reservation_date . ' ' . $request->reservation_time);
        
        $conflictingReservation = Reservation::where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'confirmed')
            ->whereBetween('reservation_time', [
                $reservationDateTime->subHours(2)->format('H:i:s'),
                $reservationDateTime->addHours(2)->format('H:i:s')
            ])
            ->exists();

        if ($conflictingReservation) {
            return response()->json([
                'success' => false,
                'message' => 'Table is not available at the requested time'
            ], 422);
        }

        // Check table capacity
        $table = Table::find($request->table_id);
        if ($request->party_size > $table->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Party size exceeds table capacity'
            ], 422);
        }

        $reservation = Reservation::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Reservation Created Successfully',
            'data' => $reservation->load('table')
        ], 201);
    }

    /**
     * Display the specified reservation
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load('table');

        return response()->json([
            'success' => true,
            'message' => 'Reservation Detail',
            'data' => $reservation
        ]);
    }

    /**
     * Update the specified reservation
     */
    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $request->validate([
            'table_id' => 'sometimes|exists:tables,id',
            'customer_name' => 'sometimes|string|max:255',
            'customer_phone' => 'sometimes|string|max:20',
            'customer_email' => 'sometimes|nullable|email',
            'party_size' => 'sometimes|integer|min:1',
            'reservation_date' => 'sometimes|date|after_or_equal:today',
            'reservation_time' => 'sometimes|date_format:H:i',
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'notes' => 'sometimes|nullable|string',
            'special_requests' => 'sometimes|nullable|string'
        ]);

        // Check if reservation can be updated
        if ($reservation->status === 'completed' || $reservation->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed or cancelled reservation'
            ], 422);
        }

        $reservation->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Reservation Updated Successfully',
            'data' => $reservation->fresh()->load('table')
        ]);
    }

    /**
     * Remove the specified reservation
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        if (!$reservation->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation cannot be cancelled'
            ], 422);
        }

        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation Deleted Successfully'
        ]);
    }

    /**
     * Confirm a reservation
     */
    public function confirm(Reservation $reservation): JsonResponse
    {
        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending reservations can be confirmed'
            ], 422);
        }

        $reservation->update(['status' => 'confirmed']);

        // Update table status if reservation is today
        if ($reservation->reservation_date->isToday()) {
            $reservation->table->update([
                'status' => 'reserved',
                'reservation_time' => $reservation->getReservationDateTimeAttribute(),
                'customer_name' => $reservation->customer_name,
                'customer_phone' => $reservation->customer_phone,
                'party_size' => $reservation->party_size
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation Confirmed Successfully',
            'data' => $reservation->fresh()->load('table')
        ]);
    }

    /**
     * Cancel a reservation
     */
    public function cancel(Reservation $reservation): JsonResponse
    {
        if (!$reservation->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation cannot be cancelled'
            ], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        // Update table status back to available if it was reserved
        if ($reservation->table->status === 'reserved') {
            $reservation->table->update([
                'status' => 'available',
                'customer_name' => null,
                'customer_phone' => null,
                'party_size' => null,
                'reservation_time' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation Cancelled Successfully',
            'data' => $reservation->fresh()
        ]);
    }

    /**
     * Get today's reservations
     */
    public function today(): JsonResponse
    {
        $reservations = Reservation::today()
                                  ->with('table')
                                  ->orderBy('reservation_time')
                                  ->get();

        return response()->json([
            'success' => true,
            'message' => 'Today Reservations',
            'data' => $reservations
        ]);
    }

    /**
     * Check table availability
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
            'party_size' => 'required|integer|min:1'
        ]);

        $table = Table::find($request->table_id);
        $reservationDateTime = Carbon::parse($request->reservation_date . ' ' . $request->reservation_time);

        // Check capacity
        if ($request->party_size > $table->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Party size exceeds table capacity',
                'available' => false
            ]);
        }

        // Check conflicts
        $conflictingReservation = Reservation::where('table_id', $request->table_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'confirmed')
            ->whereBetween('reservation_time', [
                $reservationDateTime->copy()->subHours(2)->format('H:i:s'),
                $reservationDateTime->copy()->addHours(2)->format('H:i:s')
            ])
            ->exists();

        return response()->json([
            'success' => true,
            'message' => 'Availability Check',
            'available' => !$conflictingReservation,
            'table' => $table
        ]);
    }
}
