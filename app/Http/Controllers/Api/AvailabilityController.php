<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvailabilityRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    /**
     * @var BookingService
     */
    protected $bookingService;

    /**
     * AvailabilityController constructor.
     *
     * @param BookingService $bookingService
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Get availability for a specific date.
     *
     * @param AvailabilityRequest $request
     * @return JsonResponse
     */
    public function index(AvailabilityRequest $request): JsonResponse
    {

        $date = $request->input('date');
        $availability = $this->bookingService->getAvailability($date);

        return response()->json($availability);
    }
}
