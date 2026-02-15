<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Time Slots Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the booking time slots.
    |
    */

    // Start time for bookings (24-hour format)
    'start_time' => env('BOOKING_START_TIME', '09:00'),
    
    // End time for bookings (24-hour format)
    'end_time' => env('BOOKING_END_TIME', '21:00'),
    
    // Duration of each slot in minutes
    'slot_duration' => env('BOOKING_SLOT_DURATION', 60),
    
    // Timezone for bookings
    'timezone' => env('BOOKING_TIMEZONE', 'Europe/Moscow'),
    
    /*
    |--------------------------------------------------------------------------
    | Google Calendar Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Calendar integration.
    |
    */
    
    // Google Calendar ID to use for bookings
    'google_calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    
    // Path to Google Calendar credentials file
    'google_credentials_path' => env('GOOGLE_CREDENTIALS_PATH', storage_path('app/google-calendar/credentials.json')),
    
    // Sync configuration
    'sync' => [
        // How many months ahead to sync (current month + this value)
        'months_ahead' => env('BOOKING_SYNC_MONTHS_AHEAD', 1),
        
        // How often to run sync (in minutes)
        'frequency' => env('BOOKING_SYNC_FREQUENCY', 10),
    ],
];
