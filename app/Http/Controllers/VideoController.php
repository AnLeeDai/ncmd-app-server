<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdView;
use App\Models\UserPoint;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function videos(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $ads = Ad::query()->where('is_active', true)->paginate($perPage);

        if ($ads->isEmpty()) {
            return $this->errorResponse('No videos found', 404);
        }

        return $this->paginationResponse($ads, 'Videos retrieved successfully');
    }

    public function startView(Request $request)
    {
        // validate ad_id in request body
        $data = $request->validate([
            'ad_id' => 'required|integer|exists:ads,id',
        ]);

        $adId = $data['ad_id'];

        $ad = Ad::query()->find($adId);

        if (!$ad || !$ad->is_active) {
            return $this->errorResponse('Video not found', 404);
        }

        $userId = auth()->id();

        // eager-load the ad when we will need it later and use indexed columns in where to take advantage of DB indexes
        $existingView = AdView::query()
            ->where('user_id', $userId)
            ->where('ad_id', $adId)
            ->whereNull('completed_at')
            ->first();

        if ($existingView) {
            return $this->errorResponse('View already started', 400);
        }

        $otherActive = AdView::with('ad')
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->where('ad_id', '<>', $adId)
            ->first();

        if ($otherActive) {
            $otherStarted = $otherActive->started_at;
            $otherDuration = (int)$otherActive->ad->duration;
            $otherWatched = (int)$otherStarted->diffInSeconds(now());
            $otherRemaining = max(0, $otherDuration - $otherWatched);

            return $this->successResponse([
                'active_ad' => [
                    'id' => $otherActive->ad->id,
                    'title' => $otherActive->ad->title,
                    'duration' => $otherDuration,
                    'points' => $otherActive->ad->points_reward,
                ],
                'started_at' => $otherStarted?->toDateTimeString(),
                'watched_seconds' => $otherWatched,
                'remaining_seconds' => $otherRemaining,
            ], 'You have another active view. Finish or cancel it before starting a new one', 400);
        }

        $adView = AdView::create([
            'user_id' => $userId,
            'ad_id' => $adId,
            'started_at' => now(),
            'points_awarded' => false,
        ]);

        return $this->successResponse([
            'ad_view_id' => $adView->id,
            'video' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'url' => $ad->video_url,
                'duration' => $ad->duration,
                'points' => $ad->points_reward,
                'poster' => $ad->poster,
            ]
        ], 'View started');
    }

    public function endView(Request $request)
    {
        $data = $request->validate([
            'ad_id' => 'required|integer|exists:ads,id',
        ]);

        $adId = $data['ad_id'];

        $userId = auth()->id();

        $adView = AdView::with('ad')
            ->where('user_id', $userId)
            ->where('ad_id', $adId)
            ->whereNull('completed_at')
            ->first();

        if (!$adView) {
            $otherActive = AdView::with('ad')
                ->where('user_id', $userId)
                ->whereNull('completed_at')
                ->first();

            if ($otherActive) {
                $otherStarted = $otherActive->started_at;
                $otherDuration = (int)$otherActive->ad->duration;
                $otherWatched = (int)$otherStarted->diffInSeconds(now());
                $otherRemaining = max(0, $otherDuration - $otherWatched);

                return $this->successResponse([
                    'active_ad' => [
                        'id' => $otherActive->ad->id,
                        'title' => $otherActive->ad->title,
                        'duration' => $otherDuration,
                        'points' => $otherActive->ad->points_reward,
                    ],
                    'started_at' => $otherStarted?->toDateTimeString(),
                    'watched_seconds' => $otherWatched,
                    'remaining_seconds' => $otherRemaining,
                ], 'No active view found for this video. You have another active view.', 400);
            }

            return $this->errorResponse('No active view found. Call start-view first', 400);
        }

        $startedAt = $adView->started_at;
        $watchedSeconds = $startedAt ? (int)$startedAt->diffInSeconds(now()) : 0;

        if (!$adView->points_awarded) {
            UserPoint::create([
                'user_id' => $adView->user_id,
                'points' => $adView->ad->points_reward,
                'source' => 'ad_view',
                'description' => "Points awarded for watching ad: {$adView->ad->title}",
            ]);
        }

        $adView->update([
            'completed_at' => now(),
            'points_awarded' => true,
        ]);

        return $this->successResponse([
            'points' => $adView->ad->points_reward,
            'watched_seconds' => $watchedSeconds,
            'started_at' => $adView->started_at?->toDateTimeString(),
            'video' => [
                'id' => $adView->ad->id,
                'title' => $adView->ad->title,
                'duration' => $adView->ad->duration,
                'points' => $adView->ad->points_reward,
            ],
        ], 'View completed, points awarded');
    }

    public function cancelView(Request $request)
    {
        $data = $request->validate([
            'ad_id' => 'required|integer|exists:ads,id',
        ]);

        $adId = $data['ad_id'];

        $userId = auth()->id();

        $adView = AdView::query()
            ->where('user_id', $userId)
            ->where('ad_id', $adId)
            ->whereNull('completed_at')
            ->first();

        if (!$adView) {
            return $this->errorResponse('No active view found to cancel', 400);
        }

        $adView->update([
            'completed_at' => now(),
            'points_awarded' => false,
        ]);

        return $this->successResponse(null, 'View cancelled');
    }
}
