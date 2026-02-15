<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingRequest;
use App\Http\Requests\Api\BookingPeriodRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    /**
     * @var BookingService
     */
    protected $bookingService;

    /**
     * BookingController constructor.
     *
     * @param BookingService $bookingService
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Create a new booking.
     *
     * @param BookingRequest $request
     * @return JsonResponse
     */
    public function store(BookingRequest $request): JsonResponse
    {

        try {
            $booking = $this->bookingService->createBooking(
                $request->input('date'),
                $request->input('slot')
            );

            return response()->json([
                'id' => $booking->id,
                'status' => $booking->status
            ], 201);
        } catch (\Exception $e) {
            $statusCode = 500;
            $message = 'An error occurred while creating the booking';

            // Handle specific error cases
            if (strpos($e->getMessage(), 'already booked') !== false) {
                $statusCode = 409; // Conflict
                $message = 'This slot is already booked';
            } elseif (strpos($e->getMessage(), 'past') !== false) {
                $statusCode = 422; // Unprocessable Entity
                $message = 'Cannot book dates in the past';
            } elseif (strpos($e->getMessage(), 'Google Calendar') !== false) {
                $statusCode = 500; // Server Error
                $message = 'Error with Google Calendar integration';
            }

            return response()->json([
                'message' => $message,
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Cancel a booking.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->cancelBooking($id);

            return response()->json([
                'id' => $booking->id,
                'status' => $booking->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Booking not found or could not be cancelled',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Get bookings for a period.
     *
     * @param BookingPeriodRequest $request
     * @return JsonResponse
     */
    public function period(BookingPeriodRequest $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $days = $request->input('days', 90); // Default to 90 days if not specified
        
        $bookings = $this->bookingService->getBookingsForPeriod($startDate, $days);
        
        return response()->json($bookings);
    }
}
