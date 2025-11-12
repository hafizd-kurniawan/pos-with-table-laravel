<?php

namespace App\Observers;

use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class ReservationObserver
{
    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        // When reservation is created with confirmed status, update table
        if ($reservation->status === 'confirmed') {
            $this->updateTableFromReservation($reservation);
        }

        Log::info('Reservation created', [
            'reservation_id' => $reservation->id,
            'table_id' => $reservation->table_id,
            'status' => $reservation->status,
            'customer' => $reservation->customer_name,
        ]);
    }

    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        // Only process if status changed
        if ($reservation->isDirty('status')) {
            $oldStatus = $reservation->getOriginal('status');
            $newStatus = $reservation->status;

            Log::info('Reservation status changed', [
                'reservation_id' => $reservation->id,
                'table_id' => $reservation->table_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'customer' => $reservation->customer_name,
            ]);

            // Update table based on new status
            $this->updateTableFromReservation($reservation);
        }
    }

    /**
     * Handle the Reservation "deleted" event.
     */
    public function deleted(Reservation $reservation): void
    {
        // When reservation is deleted, make table available
        if ($reservation->table) {
            $reservation->table->update([
                'status' => 'available',
                'party_size' => 0,
                'customer_name' => null,
                'customer_phone' => null,
                'reservation_time' => null,
            ]);

            Log::info('Reservation deleted - table made available', [
                'reservation_id' => $reservation->id,
                'table_id' => $reservation->table_id,
                'table_name' => $reservation->table->name,
            ]);
        }
    }

    /**
     * Handle the Reservation "restored" event.
     */
    public function restored(Reservation $reservation): void
    {
        // When restored, update table based on current status
        $this->updateTableFromReservation($reservation);
    }

    /**
     * Handle the Reservation "force deleted" event.
     */
    public function forceDeleted(Reservation $reservation): void
    {
        // Same as regular delete - make table available
        if ($reservation->table) {
            $reservation->table->update([
                'status' => 'available',
                'party_size' => 0,
                'customer_name' => null,
                'customer_phone' => null,
                'reservation_time' => null,
            ]);
        }
    }

    /**
     * Update table status and info based on reservation
     */
    private function updateTableFromReservation(Reservation $reservation): void
    {
        if (!$reservation->table) {
            return;
        }

        $table = $reservation->table;

        // Determine table status based on reservation status
        $tableStatus = match ($reservation->status) {
            'confirmed' => 'reserved',
            'checked_in' => 'occupied',
            'completed', 'cancelled', 'no_show' => 'available',
            default => $table->status, // Keep current status for pending
        };

        // Update table data
        $updateData = [
            'status' => $tableStatus,
        ];

        // Add customer info and reservation time for active reservations
        if (in_array($reservation->status, ['confirmed', 'checked_in'])) {
            $updateData['customer_name'] = $reservation->customer_name;
            $updateData['customer_phone'] = $reservation->customer_phone;
            $updateData['party_size'] = $reservation->party_size;
            
            // Combine date and time properly
            // reservation_time is TIME column (H:i:s) in database
            $date = $reservation->reservation_date->format('Y-m-d');
            $time = $reservation->reservation_time; // Raw time from DB (H:i:s)
            
            $updateData['reservation_time'] = "{$date} {$time}";
        } else {
            // Clear customer info for completed/cancelled reservations
            $updateData['customer_name'] = null;
            $updateData['customer_phone'] = null;
            $updateData['party_size'] = 0;
            $updateData['reservation_time'] = null;
        }

        $table->update($updateData);

        Log::info('Table updated from reservation', [
            'table_id' => $table->id,
            'table_name' => $table->name,
            'new_status' => $tableStatus,
            'reservation_status' => $reservation->status,
            'customer' => $reservation->customer_name,
        ]);
    }
}
