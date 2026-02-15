# Calendar API Documentation

This document provides information about the Studio Booking API endpoints, request/response formats, and examples.

## Base URL

```
http://localhost:8000/api
```

## Authentication

Currently, the API does not require authentication.

## Endpoints

### 1. Get Bookings for a Period

Returns all bookings within a specified period starting from a given date. This is useful for displaying bookings in a calendar view without making multiple requests.

**Endpoint:** `GET /bookings/period`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | string | Yes | Start date in YYYY-MM-DD format |
| days | integer | No | Number of days to include (default: 90, max: 365) |

**Response:**

```json
{
  "start_date": "2026-02-20",
  "end_date": "2026-05-20",
  "days": 90,
  "bookings": {
    "2026-02-20": [
      {
        "id": 123,
        "slot_start": "10:00",
        "slot_end": "11:00"
      },
      {
        "id": 124,
        "slot_start": "15:00",
        "slot_end": "16:00"
      }
    ],
    "2026-02-21": [],
    "2026-02-22": [
      {
        "id": 125,
        "slot_start": "12:00",
        "slot_end": "13:00"
      }
    ]
    // ... entries for all days in the period
  }
}
```

The response includes:
- All dates in the requested period, even those with no bookings (empty arrays)
- For each date with bookings, an array of booking objects with ID and time slots

**Example Request:**

```
GET /api/bookings/period?start_date=2026-02-20&days=90
```

**Error Responses:**

- `422 Unprocessable Entity` - Invalid date format or invalid days parameter

### 2. Get Availability

Returns the availability status for all time slots on a specific date.

**Endpoint:** `GET /availability`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | string | Yes | Date in YYYY-MM-DD format |

**Response:**

```json
{
  "date": "2026-02-20",
  "slots": {
    "09:00": false,
    "10:00": true,
    "11:00": false,
    "12:00": false,
    "13:00": false,
    "14:00": false,
    "15:00": false,
    "16:00": false,
    "17:00": false,
    "18:00": false,
    "19:00": false,
    "20:00": false
  }
}
```

Where:
- `true` means the slot is booked (unavailable)
- `false` means the slot is free (available)

**Example Request:**

```
GET /api/availability?date=2026-02-20
```

**Error Responses:**

- `422 Unprocessable Entity` - Invalid date format or missing date parameter

### 2. Create Booking

Creates a new booking for a specific date and time slot.

**Endpoint:** `POST /bookings`

**Request Body:**

```json
{
  "date": "2026-02-20",
  "slot": "10:00"
}
```

**Response:**

```json
{
  "id": 123,
  "status": "confirmed"
}
```

**Error Responses:**

- `409 Conflict` - Slot is already booked
- `422 Unprocessable Entity` - Invalid data or booking in the past
- `500 Internal Server Error` - Error with Google Calendar integration

### 3. Cancel Booking

Cancels an existing booking.

**Endpoint:** `DELETE /bookings/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Booking ID |

**Response:**

```json
{
  "id": 123,
  "status": "cancelled"
}
```

**Error Responses:**

- `404 Not Found` - Booking not found

## Data Validation Rules

### Date

- Must be in YYYY-MM-DD format
- Cannot be in the past for booking creation

### Time Slot

- Must be in HH:MM format
- Must be on the hour (e.g., 09:00, 10:00)
- Must be between 09:00 and 20:00

## Google Calendar Integration

The API automatically synchronizes bookings with Google Calendar:

1. When a booking is created, a corresponding event is created in Google Calendar
2. When a booking is cancelled, the corresponding event is deleted from Google Calendar
3. A scheduled task runs every 10 minutes to synchronize changes made directly in Google Calendar

## Error Handling

All API errors return a JSON response with the following structure:

```json
{
  "message": "Error message",
  "errors": {
    "field_name": [
      "Error description"
    ]
  }
}
```

## Rate Limiting

The API implements rate limiting to prevent abuse. Clients are limited to 60 requests per minute.

## Timezone

All dates and times are handled in the Europe/Moscow timezone by default. This can be configured in the application settings.
