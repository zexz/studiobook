<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-google-calendar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize bookings with Google Calendar';

    /**
     * Execute the console command.
     */
    /**
     * @var GoogleCalendarService
     */
    protected $googleCalendarService;

    /**
     * Create a new command instance.
     *
     * @param GoogleCalendarService $googleCalendarService
     */
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        parent::__construct();
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Google Calendar synchronization...');

        try {
            // Calculate date range for sync
            $startDate = Carbon::today()->format('Y-m-d');
            $monthsAhead = config('booking.sync.months_ahead', 1);
            $endDate = Carbon::today()->addMonths($monthsAhead)->endOfMonth()->format('Y-m-d');

            $this->info("Syncing events from {$startDate} to {$endDate}");

            // Get events from Google Calendar
            $events = $this->googleCalendarService->syncEvents($startDate, $endDate);
            $this->info('Retrieved ' . count($events) . ' events from Google Calendar');

            $updated = 0;
            $skipped = 0;

            // Process each event
            foreach ($events as $event) {
                // Find booking by Google event ID
                $booking = Booking::where('google_event_id', $event['google_event_id'])->first();

                if ($booking) {
                    // If event is cancelled in Google Calendar, update booking status
                    if ($event['status'] === 'cancelled' && $booking->status !== 'cancelled') {
                        $booking->status = 'cancelled';
                        $booking->save();
                        $updated++;
                        $this->info("Updated booking #{$booking->id} to cancelled status");
                    }
                } else {
                    // Skip events that don't match our booking pattern
                    // This prevents importing unrelated calendar events
                    if (!str_contains($event['summary'], 'Studio Booking')) {
                        $skipped++;
                        continue;
                    }

                    // Parse event start time
                    $eventStart = Carbon::parse($event['start']);
                    $date = $eventStart->format('Y-m-d');
                    $slotStart = $eventStart->format('H:i');
                    $slotEnd = Carbon::parse($event['end'])->format('H:i');

                    // Check if there's already a booking at this time slot
                    $existingBooking = Booking::where('date', $date)
                        ->where('slot_start', $slotStart)
                        ->first();

                    if (!$existingBooking) {
                        // Create new booking from Google Calendar event
                        Booking::create([
                            'date' => $date,
                            'slot_start' => $slotStart,
                            'slot_end' => $slotEnd,
                            'status' => 'confirmed',
                            'google_event_id' => $event['google_event_id']
                        ]);
                        $updated++;
                        $this->info("Created new booking for {$date} at {$slotStart}");
                    }
                }
            }

            $this->info("Synchronization completed: {$updated} bookings updated/created, {$skipped} events skipped");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Synchronization failed: ' . $e->getMessage());
            Log::error('Google Calendar sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
