<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    private const GAME_SLUG = 'bulletdrop';

    public function index(Request $request, ?string $game = null): JsonResponse
    {
        $gameSlug = $game ?: self::GAME_SLUG;
        $this->ensureSupportedGame($gameSlug);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $limit = $validated['limit'] ?? 25;
        $offset = $validated['offset'] ?? 0;

        $bestScores = DB::table('game_scores')
            ->selectRaw('user_id, MAX(score) as best_score, COUNT(*) as scores_submitted, MAX(submitted_at) as last_submitted_at')
            ->where('game_slug', $gameSlug)
            ->groupBy('user_id');

        $totalPlayers = DB::query()
            ->fromSub(clone $bestScores, 'best')
            ->count();

        $rows = DB::query()
            ->fromSub($bestScores, 'best')
            ->join('users', 'users.id', '=', 'best.user_id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.username',
                'best.best_score',
                'best.scores_submitted',
                'best.last_submitted_at',
            ])
            ->orderByDesc('best.best_score')
            ->orderBy('best.last_submitted_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'game' => $gameSlug,
            'limit' => $limit,
            'offset' => $offset,
            'total_players' => $totalPlayers,
            'leaderboard' => $rows->values()->map(function ($row, int $index) use ($offset) {
                return [
                    'rank' => $offset + $index + 1,
                    'user_id' => $row->user_id,
                    'username' => $row->username,
                    'display_name' => $row->username ?: $row->name,
                    'score' => (int) $row->best_score,
                    'scores_submitted' => (int) $row->scores_submitted,
                    'last_submitted_at' => $row->last_submitted_at,
                ];
            }),
        ]);
    }

    private function ensureSupportedGame(string $game): void
    {
        if ($game !== self::GAME_SLUG) {
            abort(404, 'Game not found.');
        }
    }
}
