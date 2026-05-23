<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GameScore;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GameController extends Controller
{
    private const GAME_SLUG = 'bulletdrop';

    public function index(): JsonResponse
    {
        return response()->json([
            'games' => [
                $this->gamePayload(),
            ],
        ]);
    }

    public function show(string $game): JsonResponse
    {
        $this->ensureSupportedGame($game);

        return response()->json([
            'game' => $this->gamePayload(),
        ]);
    }

    public function startSession(Request $request, string $game): JsonResponse
    {
        $this->ensureSupportedGame($game);

        $validated = $request->validate([
            'metadata' => ['nullable', 'array'],
        ]);

        $session = GameSession::create([
            'user_id' => $request->user()->id,
            'game_slug' => self::GAME_SLUG,
            'status' => 'started',
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'message' => 'Game session started.',
            'session' => $this->sessionPayload($session),
            'launch_url' => route('game.play'),
        ], 201);
    }

    public function endSession(Request $request, string $game, GameSession $session): JsonResponse
    {
        $this->ensureSupportedGame($game);
        $this->ensureOwnSession($request, $session);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['completed', 'quit', 'crashed'])],
            'metadata' => ['nullable', 'array'],
        ]);

        $session->forceFill([
            'status' => $validated['status'] ?? 'completed',
            'ended_at' => now(),
            'metadata' => array_filter([
                ...($session->metadata ?? []),
                ...($validated['metadata'] ?? []),
            ], fn ($value) => $value !== null),
        ])->save();

        return response()->json([
            'message' => 'Game session updated.',
            'session' => $this->sessionPayload($session),
        ]);
    }

    public function submitScore(Request $request, string $game): JsonResponse
    {
        $this->ensureSupportedGame($game);

        $validated = $request->validate([
            'game_session_id' => ['nullable', 'integer', 'exists:game_sessions,id'],
            'score' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'level' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'accuracy' => ['nullable', 'integer', 'min:0', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $session = null;

        if (isset($validated['game_session_id'])) {
            $session = GameSession::findOrFail($validated['game_session_id']);
            $this->ensureOwnSession($request, $session);

            if ($session->game_slug !== self::GAME_SLUG) {
                throw ValidationException::withMessages([
                    'game_session_id' => ['The game session does not belong to this game.'],
                ]);
            }
        }

        $score = GameScore::create([
            'user_id' => $request->user()->id,
            'game_session_id' => $session?->id,
            'game_slug' => self::GAME_SLUG,
            'score' => $validated['score'],
            'level' => $validated['level'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'accuracy' => $validated['accuracy'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'submitted_at' => now(),
        ]);

        if ($session && $session->status === 'started') {
            $session->forceFill([
                'status' => 'completed',
                'ended_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Score submitted.',
            'score' => $this->scorePayload($score),
            'personal_best' => $this->personalBest($request),
            'rank' => $this->rankForScore($score->score),
        ], 201);
    }

    public function me(Request $request, string $game): JsonResponse
    {
        $this->ensureSupportedGame($game);

        $bestScore = $this->personalBest($request);

        return response()->json([
            'game' => self::GAME_SLUG,
            'stats' => [
                'plays' => GameSession::where('user_id', $request->user()->id)
                    ->where('game_slug', self::GAME_SLUG)
                    ->count(),
                'scores_submitted' => GameScore::where('user_id', $request->user()->id)
                    ->where('game_slug', self::GAME_SLUG)
                    ->count(),
                'personal_best' => $bestScore,
                'rank' => $bestScore ? $this->rankForScore($bestScore['score']) : null,
            ],
        ]);
    }

    private function ensureSupportedGame(string $game): void
    {
        if ($game !== self::GAME_SLUG) {
            abort(404, 'Game not found.');
        }
    }

    private function ensureOwnSession(Request $request, GameSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'This game session belongs to another user.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function gamePayload(): array
    {
        return [
            'slug' => self::GAME_SLUG,
            'title' => 'BulletDrop',
            'subtitle' => 'Fly and shoot to survive',
            'version' => 'v206',
            'platforms' => ['desktop_browser', 'mobile_browser'],
            'launch_url' => route('game.play'),
            'launcher_url' => route('launcher'),
            'manifest_url' => asset('game/manifest.webmanifest'),
            'icon_url' => asset('game/icon-192.png'),
            'leaderboard_url' => url('/api/v1/games/'.self::GAME_SLUG.'/leaderboard'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(GameSession $session): array
    {
        return [
            'id' => $session->id,
            'game' => $session->game_slug,
            'status' => $session->status,
            'started_at' => $session->started_at?->toISOString(),
            'ended_at' => $session->ended_at?->toISOString(),
            'metadata' => $session->metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scorePayload(GameScore $score): array
    {
        return [
            'id' => $score->id,
            'game' => $score->game_slug,
            'score' => $score->score,
            'level' => $score->level,
            'duration_seconds' => $score->duration_seconds,
            'accuracy' => $score->accuracy,
            'submitted_at' => $score->submitted_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function personalBest(Request $request): ?array
    {
        $score = GameScore::where('user_id', $request->user()->id)
            ->where('game_slug', self::GAME_SLUG)
            ->orderByDesc('score')
            ->orderBy('submitted_at')
            ->first();

        return $score ? $this->scorePayload($score) : null;
    }

    private function rankForScore(int $score): int
    {
        $bestScores = GameScore::query()
            ->selectRaw('user_id, MAX(score) as best_score')
            ->where('game_slug', self::GAME_SLUG)
            ->groupBy('user_id');

        $higherScores = DB::query()
            ->fromSub($bestScores, 'best')
            ->where('best_score', '>', $score)
            ->count();

        return $higherScores + 1;
    }
}
