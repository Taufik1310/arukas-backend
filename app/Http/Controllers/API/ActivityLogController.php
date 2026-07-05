<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user:id,name,email')
            ->when($request->search, fn($q, $s) =>
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('action', 'like', "%{$s}%"))
            ->when($request->action, fn($q, $a) => $q->where('action', $a))
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->subject_type, fn($q, $t) => $q->where('subject_type', $t))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date,   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => true,
            'data'   => $logs->items(),
            'meta'   => ['total' => $logs->total(), 'last_page' => $logs->lastPage(), 'current_page' => $logs->currentPage()],
        ]);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $activityLog->load('user:id,name')]);
    }

    public function actions(): JsonResponse
    {
        $actions = ActivityLog::distinct()->pluck('action');
        return response()->json(['status' => true, 'data' => $actions]);
    }
}
