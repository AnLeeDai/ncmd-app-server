<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\UserPoint;
use App\Models\User;

class PointController extends Controller
{
    public function pointHistoryUser(Request $request)
    {
        $data = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'source' => 'sometimes|string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
        ]);

        $user = $request->user() ?? Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $perPage = $request->input('per_page', 10);

        $query = UserPoint::where('user_id', $user->id);

        if (!empty($data['source'])) {
            $query->where('source', $data['source']);
        }

        if (!empty($data['date_from'])) {
            $from = Carbon::parse($data['date_from'])->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if (!empty($data['date_to'])) {
            $to = Carbon::parse($data['date_to'])->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        $query->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'points' => $item->points,
                'type' => $item->points >= 0 ? 'credit' : 'debit',
                'source' => $item->source,
                'description' => $item->description,
                'created_at' => $item->created_at,
            ];
        });

        $balance = UserPoint::where('user_id', $user->id)->sum('points');

        if ($paginator->isEmpty()) {
            return $this->errorResponse('No point history found', 404);
        }

        return $this->paginationResponse(
            $paginator,
            'Point history retrieved successfully',
            200,
            ['balance' => $balance]
        );
    }

    public function pointHistoryById(Request $request, $userId)
    {
        $data = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'source' => 'sometimes|string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
        ]);

        $auth = $request->user() ?? Auth::user();

        if (!$auth) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        if (!$auth->isAdmin()) {
            return $this->errorResponse('Forbidden. Admins only.', 403);
        }

        if (!is_numeric($userId) || intval($userId) <= 0) {
            return $this->errorResponse('Invalid user id', 400);
        }

        $userId = intval($userId);

        $target = User::find($userId);
        if (!$target) {
            return $this->errorResponse('User not found', 404);
        }

        $perPage = $request->input('per_page', 10);

        $query = UserPoint::where('user_id', $userId);

        if (!empty($data['source'])) {
            $query->where('source', $data['source']);
        }

        if (!empty($data['date_from'])) {
            $from = Carbon::parse($data['date_from'])->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if (!empty($data['date_to'])) {
            $to = Carbon::parse($data['date_to'])->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        $query->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'points' => $item->points,
                'type' => $item->points >= 0 ? 'credit' : 'debit',
                'source' => $item->source,
                'description' => $item->description,
                'created_at' => $item->created_at,
            ];
        });

        $balance = UserPoint::where('user_id', $userId)->sum('points');

        if ($paginator->isEmpty()) {
            return $this->errorResponse('No point history found for this user', 404);
        }

        return $this->paginationResponse(
            $paginator,
            'Point history retrieved successfully',
            200,
            ['balance' => $balance]
        );
    }
}