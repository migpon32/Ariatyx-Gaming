# Ariatyx Gaming REST API v1

Base URL:

```text
/api/v1
```

Authenticated requests use a Sanctum bearer token:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

## Register

```http
POST /api/v1/register
```

Creates the user, creates a 2FA secret, and returns a setup-only token plus QR details. The account must confirm 2FA before receiving a full API token.

Required JSON:

```json
{
  "username": "PlayerOne",
  "email": "player@example.com",
  "password": "password",
  "password_confirmation": "password",
  "security_question_1": "Question 1",
  "security_answer_1": "ANSWER",
  "security_question_2": "Question 2",
  "security_answer_2": "ANSWER",
  "security_question_3": "Question 3",
  "security_answer_3": "ANSWER",
  "device_name": "Android Phone"
}
```

## Login

```http
POST /api/v1/login
```

Checks email and password. If 2FA is enabled, no full token is returned yet. The response includes a short-lived `challenge_token`.

```json
{
  "email": "player@example.com",
  "password": "password",
  "device_name": "Android Phone"
}
```

## Complete Login 2FA

```http
POST /api/v1/login/two-factor
```

Verifies the authenticator code or one recovery code, then returns the full Sanctum token.

```json
{
  "challenge_token": "64-character-token",
  "code": "123456"
}
```

Recovery-code fallback:

```json
{
  "challenge_token": "64-character-token",
  "recovery_code": "ABCDE-FGHIJ"
}
```

## Current User

```http
GET /api/v1/me
```

Returns the authenticated user and 2FA status.

## 2FA Setup

```http
GET /api/v1/two-factor/setup
```

Returns the manual setup key, `otpauth_url`, QR code SVG, and compatible authenticator apps.

## Confirm 2FA Setup

```http
POST /api/v1/two-factor/confirm
```

Verifies the first 6-digit authenticator code, enables 2FA, returns recovery codes, and returns a full API token.

```json
{
  "code": "123456",
  "device_name": "Android Phone"
}
```

## Recovery Codes Status

```http
GET /api/v1/two-factor/recovery-codes
```

Returns how many unused recovery codes remain. Existing recovery codes cannot be displayed again.

## Regenerate Recovery Codes

```http
POST /api/v1/two-factor/recovery-codes
```

Requires the current password. Returns a fresh set of recovery codes and invalidates old ones.

```json
{
  "password": "password"
}
```

## Disable 2FA

```http
DELETE /api/v1/two-factor
```

Requires the current password. Disables 2FA, deletes remembered web devices, revokes old API tokens, and returns a setup-only token so 2FA can be configured again.

```json
{
  "password": "password",
  "device_name": "Android Phone"
}
```

## Logout

```http
POST /api/v1/logout
```

Deletes only the current Sanctum token.

## List Games

```http
GET /api/v1/games
```

Returns the games exposed by Ariatyx Gaming. Right now this returns BulletDrop metadata, including launch URL, icon URL, manifest URL, and leaderboard URL. This endpoint is public.

## Game Details

```http
GET /api/v1/games/bulletdrop
```

Returns one game record. Use this when a launcher or mobile client needs the exact game slug, title, browser launch URL, PWA manifest, and leaderboard API URL.

## Start Game Session

```http
POST /api/v1/games/bulletdrop/sessions
```

Requires a full Bearer token after 2FA. Creates a play session for the authenticated user and returns a `session.id`. Store this ID in the client and send it back when ending the session or submitting a score.

Optional JSON:

```json
{
  "metadata": {
    "client": "webgl",
    "build": "v206"
  }
}
```

## End Game Session

```http
PATCH /api/v1/games/bulletdrop/sessions/{session_id}
```

Requires a full Bearer token after 2FA. Marks the user's own session as `completed`, `quit`, or `crashed`.

```json
{
  "status": "completed",
  "metadata": {
    "reason": "player_exit"
  }
}
```

## Submit Score

```http
POST /api/v1/games/bulletdrop/scores
```

Requires a full Bearer token after 2FA. Saves one score row, optionally linked to a game session. The response includes the submitted score, the player's personal best, and their rank based on best score per player.

```json
{
  "game_session_id": 1,
  "score": 12500,
  "level": 4,
  "duration_seconds": 312,
  "accuracy": 87,
  "metadata": {
    "mode": "normal"
  }
}
```

## Player Game Stats

```http
GET /api/v1/games/bulletdrop/me
```

Requires a full Bearer token after 2FA. Returns the authenticated player's play count, submitted score count, personal best, and current rank.

## Game Leaderboard

```http
GET /api/v1/games/bulletdrop/leaderboard
```

Public endpoint. Returns ranked players by their best BulletDrop score. Each player appears once, even if they submitted many scores.

Optional query parameters:

```text
limit=25
offset=0
```

Shortcut endpoint:

```http
GET /api/v1/leaderboard
```

This returns the default BulletDrop leaderboard.
