<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    /**
     * @var Calendar
     */
    protected $calendarService;

    /**
     * @var string
     */
    protected $calendarId;

    /**
     * GoogleCalendarService constructor.
     */
    public function __construct()
    {
        $this->calendarId = config('services.google.calendar_id', 'primary');
        
        try {
            $client = $this->getClient();
            $this->calendarService = new Calendar($client);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Calendar service: ' . $e->getMessage());
            $this->calendarService = null;
        }
    }

    /**
     * Create a Google Calendar event.
     *
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @param string $summary
     * @return string Event ID
     * @throws \Exception
     */
    public function createEvent(string $date, string $startTime, string $endTime, string $summary): string
    {
        if (!$this->calendarService) {
            throw new \Exception('Google Calendar service not initialized');
        }

        $timezone = config('app.timezone', 'UTC');
        
        $startDateTime = Carbon::parse("$date $startTime", $timezone);
        $endDateTime = Carbon::parse("$date $endTime", $timezone);
        
        $event = new Event([
            'summary' => $summary,
            'description' => 'Booking created via Studio Booking API',
            'start' => [
                'dateTime' => $startDateTime->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $endDateTime->toRfc3339String(),
                'timeZone' => $timezone,
            ],
        ]);
        
        $createdEvent = $this->calendarService->events->insert($this->calendarId, $event);
        
        return $createdEvent->getId();
    }

    /**
     * Delete a Google Calendar event.
     *
     * @param string $eventId
     * @return bool
     * @throws \Exception
     */
    public function deleteEvent(string $eventId): bool
    {
        if (!$this->calendarService) {
            throw new \Exception('Google Calendar service not initialized');
        }
        
        $this->calendarService->events->delete($this->calendarId, $eventId);
        
        return true;
    }

    /**
     * Synchronize events from Google Calendar.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     * @throws \Exception
     */
    public function syncEvents(string $startDate, string $endDate): array
    {
        if (!$this->calendarService) {
            throw new \Exception('Google Calendar service not initialized');
        }
        
        $timezone = config('app.timezone', 'UTC');
        
        $startDateTime = Carbon::parse($startDate, $timezone)->startOfDay();
        $endDateTime = Carbon::parse($endDate, $timezone)->endOfDay();
        
        $events = $this->calendarService->events->listEvents(
            $this->calendarId,
            [
                'timeMin' => $startDateTime->toRfc3339String(),
                'timeMax' => $endDateTime->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]
        );
        
        $syncedEvents = [];
        foreach ($events->getItems() as $event) {
            $syncedEvents[] = [
                'google_event_id' => $event->getId(),
                'summary' => $event->getSummary(),
                'start' => Carbon::parse($event->getStart()->getDateTime())->format('Y-m-d H:i'),
                'end' => Carbon::parse($event->getEnd()->getDateTime())->format('Y-m-d H:i'),
                'status' => $event->getStatus(),
            ];
        }
        
        return $syncedEvents;
    }

    /**
     * Get Google API client.
     *
     * @return Client
     * @throws \Exception
     */
    protected function getClient(): Client
    {
        $client = new Client();
        $client->setApplicationName('Studio Booking API');
        $client->setScopes([Calendar::CALENDAR]);
        
        // Check if we have credentials file
        $credentialsPath = storage_path('app/google-calendar/credentials.json');
        if (!file_exists($credentialsPath)) {
            throw new \Exception('Google Calendar credentials file not found');
        }
        
        $client->setAuthConfig($credentialsPath);
        $client->setAccessType('offline');
        
        return $client;
    }
}
