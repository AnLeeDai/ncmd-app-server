<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdView;
use App\Models\UserPoint;

class VideoController extends Controller
{
    public function videos()
    {
        $ads = Ad::where('is_active', true)->paginate(10)->through(function ($ad) {
            return [
                'id' => $ad->id,
                'title' => $ad->title,
                'url' => $ad->video_url,
                'duration' => $ad->duration,
                'points' => $ad->points_reward,
                'poster' => $ad->poster,
                'created_at' => $ad->created_at,
                'updated_at' => $ad->updated_at,
            ];
        });

        return $this->paginationResponse($ads, 'Videos retrieved successfully');
    }

    public function getVideoById($id)
    {
        $ad = Ad::find($id);

        if (!$ad || !$ad->is_active) {
            return $this->errorResponse('Video not found', 404);
        }

        $videoData = [
            'id' => $ad->id,
            'title' => $ad->title,
            'url' => $ad->video_url,
            'duration' => $ad->duration,
            'points' => $ad->points_reward,
            'poster' => $ad->poster,
            'created_at' => $ad->created_at,
            'updated_at' => $ad->updated_at,
        ];

        return $this->successResponse($videoData, 'Video retrieved successfully');
    }

    public function startView($adId)
    {
        $ad = Ad::find($adId);

        if (!$ad || !$ad->is_active) {
            return $this->errorResponse('Video not found', 404);
        }

        $userId = auth()->id();

        $existingView = AdView::where('user_id', $userId)
            ->where('ad_id', $adId)
            ->whereNull('completed_at')
            ->first();

        if ($existingView) {
            return $this->errorResponse('View already started', 400);
        }

        // Prevent starting a different video while another view is active
        $otherActive = AdView::where('user_id', $userId)
            ->whereNull('completed_at')
            ->where('ad_id', '<>', $adId)
            ->first();

        if ($otherActive) {
            $otherStarted = $otherActive->started_at;
            $otherDuration = (int) $otherActive->ad->duration;
            $otherWatched = (int) $otherStarted->diffInSeconds(now());
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

    public function endView($adId)
    {
        $userId = auth()->id();

        $adView = AdView::where('user_id', $userId)
            ->where('ad_id', $adId)
            ->whereNull('completed_at')
            ->first();

        if (!$adView) {
            $otherActive = AdView::where('user_id', $userId)
                ->whereNull('completed_at')
                ->first();

            if ($otherActive) {
                $otherStarted = $otherActive->started_at;
                $otherDuration = (int) $otherActive->ad->duration;
                $otherWatched = (int) $otherStarted->diffInSeconds(now());
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
        $duration = (int) $adView->ad->duration;

        $watchedSeconds = (int) $startedAt->diffInSeconds(now());

        if ($watchedSeconds < $duration) {
            $remaining = max(0, $duration - $watchedSeconds);

            return $this->successResponse([
                'required_seconds' => $duration,
                'watched_seconds' => $watchedSeconds,
                'remaining_seconds' => $remaining,
                'started_at' => $adView->started_at?->toDateTimeString(),
                'video' => [
                    'id' => $adView->ad->id,
                    'title' => $adView->ad->title,
                    'duration' => $adView->ad->duration,
                    'points' => $adView->ad->points_reward,
                ],
            ], 'Video not watched for full duration', 400);
        }

        UserPoint::create([
            'user_id' => $adView->user_id,
            'points' => $adView->ad->points_reward,
            'source' => 'ad_view',
            'description' => "Points awarded for watching ad: {$adView->ad->title}",
        ]);

        $adView->update([
            'completed_at' => now(),
            'points_awarded' => true,
        ]);

        return $this->successResponse(['points' => $adView->ad->points_reward], 'View completed, points awarded');
    }

    public function cancelView($adId)
    {
        $userId = auth()->id();

        $adView = AdView::where('user_id', $userId)
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
