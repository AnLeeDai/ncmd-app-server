<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SpinTurn;
use App\Models\UserPoint;
use App\Models\User;

class SpinController extends Controller
{
    public function addReward(Request $request)
    {
        // Implementation for adding a reward
    }

    public function allRewards(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $typeFilter = $request->input('type');

        $now = Carbon::now()->toDateTimeString();
        // include cost per reward and provider for phone cards (e.g. keygame)
        $rewards = collect([
            // phone cards (provider: keygame)
            ['id' => 1, 'type' => 'phone_card', 'provider' => 'keygame', 'title' => 'KeyGame 50k card', 'value' => 50000, 'unit' => 'vnd', 'cost' => 10, 'description' => 'KeyGame top-up card 50,000 VND', 'created_at' => $now],
            ['id' => 2, 'type' => 'phone_card', 'provider' => 'keygame', 'title' => 'KeyGame 100k card', 'value' => 100000, 'unit' => 'vnd', 'cost' => 20, 'description' => 'KeyGame top-up card 100,000 VND', 'created_at' => $now],
            // other phone card provider example
            ['id' => 3, 'type' => 'phone_card', 'provider' => 'mobi', 'title' => 'Mobi 50k card', 'value' => 50000, 'unit' => 'vnd', 'cost' => 8, 'description' => 'Mobi top-up card 50,000 VND', 'created_at' => $now],
            // data packs
            ['id' => 4, 'type' => 'data', 'title' => '1GB Data Pack', 'value' => 1, 'unit' => 'gb', 'cost' => 5, 'description' => '1GB mobile data', 'created_at' => $now],
            ['id' => 5, 'type' => 'data', 'title' => '5GB Data Pack', 'value' => 5, 'unit' => 'gb', 'cost' => 12, 'description' => '5GB mobile data', 'created_at' => $now],
            // good luck (no prize)
            ['id' => 6, 'type' => 'good_luck', 'title' => 'Try again - Good luck', 'value' => 0, 'unit' => null, 'cost' => 2, 'description' => 'No prize, better luck next time', 'created_at' => $now],
        ]);

        if ($typeFilter) {
            $rewards = $rewards->where('type', $typeFilter)->values();
        }

        $page = max(1, (int) $request->input('page', 1));
        $total = $rewards->count();
        $sliced = $rewards->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator($sliced, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $paginator->getCollection()->transform(fn($item) => [
            'id' => $item['id'],
            'type' => $item['type'],
            'provider' => $item['provider'] ?? null,
            'title' => $item['title'],
            'value' => $item['value'],
            'unit' => $item['unit'],
            'cost' => $item['cost'] ?? null,
            'description' => $item['description'],
            'created_at' => $item['created_at'],
        ]);

        if ($paginator->isEmpty()) {
            return $this->errorResponse('No rewards found', 404);
        }

        return $this->paginationResponse($paginator, 'Rewards retrieved successfully');
    }

    public function spin(Request $request)
    {
        // Use authenticated user (no user_id param required)
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        // check user point balance
        $balance = (int) $user->userPoints()->sum('points');

        // Optional type filter to limit possible reward types
        $typeFilter = $request->input('type');

        // same fake rewards as allRewards (include provider and cost)
        $now = Carbon::now()->toDateTimeString();
        $rewards = collect([
            ['id' => 1, 'type' => 'phone_card', 'provider' => 'keygame', 'title' => 'KeyGame 50k card', 'value' => 50000, 'unit' => 'vnd', 'cost' => 10, 'description' => 'KeyGame top-up card 50,000 VND', 'created_at' => $now],
            ['id' => 2, 'type' => 'phone_card', 'provider' => 'keygame', 'title' => 'KeyGame 100k card', 'value' => 100000, 'unit' => 'vnd', 'cost' => 20, 'description' => 'KeyGame top-up card 100,000 VND', 'created_at' => $now],
            ['id' => 3, 'type' => 'phone_card', 'provider' => 'mobi', 'title' => 'Mobi 50k card', 'value' => 50000, 'unit' => 'vnd', 'cost' => 8, 'description' => 'Mobi top-up card 50,000 VND', 'created_at' => $now],
            ['id' => 4, 'type' => 'data', 'title' => '1GB Data Pack', 'value' => 1, 'unit' => 'gb', 'cost' => 5, 'description' => '1GB mobile data', 'created_at' => $now],
            ['id' => 5, 'type' => 'data', 'title' => '5GB Data Pack', 'value' => 5, 'unit' => 'gb', 'cost' => 12, 'description' => '5GB mobile data', 'created_at' => $now],
            ['id' => 6, 'type' => 'good_luck', 'title' => 'Try again - Good luck', 'value' => 0, 'unit' => null, 'cost' => 2, 'description' => 'No prize, better luck next time', 'created_at' => $now],
        ]);

        // filter by type param (legacy) or by theme param (allows provider or type)
        if ($typeFilter) {
            $rewards = $rewards->where('type', $typeFilter)->values();
        }

        $theme = $request->input('theme');
        if ($theme) {
            $rewards = $rewards->filter(function ($r) use ($theme) {
                return (isset($r['type']) && $r['type'] === $theme)
                    || (isset($r['provider']) && $r['provider'] === $theme);
            })->values();
        }

        if ($rewards->isEmpty()) {
            return $this->errorResponse('No rewards available for the selected type', 404);
        }

        // choose a random reward
        $reward = $rewards->random();

        // determine cost for this reward (each reward can have its own cost)
        $costPerSpin = isset($reward['cost']) ? (int) $reward['cost'] : 10;

        if ($balance < $costPerSpin) {
            return $this->errorResponse("Insufficient points to play. This reward requires {$costPerSpin} points.", 400);
        }

        // perform DB changes in a transaction: deduct points and record spin result
        DB::transaction(function () use ($user, $reward, $costPerSpin) {
            // deduct points for the spin
            UserPoint::create([
                'user_id' => $user->id,
                'points' => -1 * $costPerSpin,
                'source' => 'spin_cost',
                'description' => "Consumed {$costPerSpin} points for spin",
            ]);

            // record the spin action for auditing (turns=0 since points are used)
            SpinTurn::create([
                'user_id' => $user->id,
                'turns' => 0,
                'source' => 'spin_play',
                'description' => 'Played spin — reward will be recorded in response',
            ]);

            // if reward includes points (future enhancement), grant them
            if (!empty($reward['points'])) {
                UserPoint::create([
                    'user_id' => $user->id,
                    'points' => $reward['points'],
                    'source' => 'spin_reward',
                    'description' => $reward['title'] ?? 'Spin reward',
                ]);
            }
        });

        // refresh user to get updated total points
        $user->refresh();
        $remainingPoints = (int) $user->userPoints()->sum('points');

        // return the reward details to the client
        $payload = [
            'reward' => $reward,
            'remaining_points' => $remainingPoints,
        ];

        return $this->successResponse($payload, 'Spin played successfully');
    }
}
