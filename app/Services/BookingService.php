<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * @var GoogleCalendarService
     */
    protected $googleCalendarService;

    /**
     * BookingService constructor.
     *
     * @param GoogleCalendarService $googleCalendarService
     */
    public function __construct(GoogleCalendarService $googleCalendarService = null)
    {
        $this->googleCalendarService = $googleCalendarService ?? app(GoogleCalendarService::class);
    }

    /**
     * Get bookings for a period.
     *
     * @param string $startDate
     * @param int $days
     * @return array
     */
    public function getBookingsForPeriod(string $startDate, int $days = 90): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($startDate)->addDays($days)->endOfDay();
        
        // Get all bookings in the date range
        $bookings = Booking::where('date', '>=', $start->format('Y-m-d'))
            ->where('date', '<=', $end->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->orderBy('date')
            ->orderBy('slot_start')
            ->get();
        
        // Group bookings by date
        $groupedBookings = [];
        foreach ($bookings as $booking) {
            $date = $booking->date->format('Y-m-d');
            if (!isset($groupedBookings[$date])) {
                $groupedBookings[$date] = [];
            }
            
            $groupedBookings[$date][] = [
                'id' => $booking->id,
                'slot_start' => $booking->slot_start,
                'slot_end' => $booking->slot_end
            ];
        }
        
        // Get all dates in the range
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            $end
        );
        
        // Build the result with all dates and their bookings
        $result = [];
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $result[$dateStr] = $groupedBookings[$dateStr] ?? [];
        }
        
        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days' => $days,
            'bookings' => $result
        ];
    }
    
    /**
     * Get availability for a specific date.
     *
     * @param string $date
     * @return array
     */
    public function getAvailability(string $date): array
    {
        $slots = $this->getConfiguredTimeSlots();
        $bookings = Booking::forDate($date)->confirmed()->get();
        
        $availability = [];
        foreach ($slots as $slot) {
            $slotStart = $slot['start'];
            $isBooked = $bookings->contains(function ($booking) use ($slotStart) {
                return $booking->slot_start === $slotStart;
            });
            
            $availability[$slotStart] = $isBooked;
        }
        
        return [
            'date' => $date,
            'slots' => $availability
        ];
    }

    /**
     * Create a new booking.
     *
     * @param string $date
     * @param string $slot
     * @return Booking
     * @throws \Exception
     */
    public function createBooking(string $date, string $slot): Booking
    {
        // Validate the date is not in the past
        $bookingDate = Carbon::parse($date);
        $today = Carbon::today();
        
        if ($bookingDate->lt($today)) {
            throw new \Exception('Cannot book dates in the past');
        }
        
        // Validate the slot is available
        $availability = $this->getAvailability($date);
        if (isset($availability['slots'][$slot]) && $availability['slots'][$slot]) {
            throw new \Exception('This slot is already booked');
        }
        
        // Calculate slot end time (1 hour after start)
        $slotStart = $slot;
        $slotEnd = Carbon::parse($slot)->addHour()->format('H:i');
        
        // Start a database transaction
        return DB::transaction(function () use ($date, $slotStart, $slotEnd) {
            // Create booking record
            $booking = Booking::create([
                'date' => $date,
                'slot_start' => $slotStart,
                'slot_end' => $slotEnd,
                'status' => 'confirmed'
            ]);
            
            // Create Google Calendar event
            if ($this->googleCalendarService) {
                try {
                    $eventId = $this->googleCalendarService->createEvent(
                        $booking->date,
                        $booking->slot_start,
                        $booking->slot_end,
                        'Studio Booking #' . $booking->id
                    );
                    
                    $booking->google_event_id = $eventId;
                    $booking->save();
                } catch (\Exception $e) {
                    Log::error('Failed to create Google Calendar event: ' . $e->getMessage());
                    throw $e;
                }
            }
            
            return $booking;
        });
    }

    /**
     * Cancel a booking.
     *
     * @param int $bookingId
     * @return Booking
     * @throws \Exception
     */
    public function cancelBooking(int $bookingId): Booking
    {
        $booking = Booking::findOrFail($bookingId);
        
        return DB::transaction(function () use ($booking) {
            $booking->status = 'cancelled';
            $booking->save();
            
            // Delete Google Calendar event
            if ($booking->google_event_id && $this->googleCalendarService) {
                try {
                    $this->googleCalendarService->deleteEvent($booking->google_event_id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete Google Calendar event: ' . $e->getMessage());
                    // We don't throw here to allow the booking to be cancelled even if Google Calendar fails
                }
            }
            
            return $booking;
        });
    }

    /**
     * Get configured time slots.
     *
     * @return array
     */
    protected function getConfiguredTimeSlots(): array
    {
        $startTime = config('booking.start_time', '09:00');
        $endTime = config('booking.end_time', '21:00');
        $slotDuration = config('booking.slot_duration', 60); // in minutes
        
        $startHour = (int)substr($startTime, 0, 2);
        $endHour = (int)substr($endTime, 0, 2);
        $durationHours = $slotDuration / 60;
        
        $slots = [];
        for ($hour = $startHour; $hour < $endHour; $hour += $durationHours) {
            $start = sprintf('%02d:00', $hour);
            $end = sprintf('%02d:00', $hour + $durationHours);
            
            $slots[] = [
                'start' => $start,
                'end' => $end
            ];
        }
        
        return $slots;
    }
}
