<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Field;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'fields' => Field::count(),
            'active_fields' => Field::where('is_active', true)->count(),
            'categories' => SportCategory::count(),
            'users' => User::where('role', 'user')->count(),
            'bookings' => Booking::count(),
            'pending' => Booking::where('status', BookingStatus::Pending)->count(),
            'confirmed' => Booking::where('status', BookingStatus::Confirmed)->count(),
            'revenue' => Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->sum('total_price'),
        ];

        $recent = Booking::with(['user', 'field.sportCategory', 'slot.schedule'])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}
