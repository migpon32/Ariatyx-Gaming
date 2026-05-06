import os
import csv
import json
import requests
import pandas as pd
from flask import Flask, render_template_string

app = Flask(__name__)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CSV_FILE = os.path.join(BASE_DIR, "leaderboard_analytics.csv")

SERVER_API_KEY = os.environ.get("LOOTLOCKER_SERVER_API_KEY", "dev_9bfb5807a5f64ea78d225fdfe6de70a2")
LEADERBOARD_KEY = os.environ.get("LEADERBOARD_KEY", "main_leaderboard")
LARAVEL_URL = os.environ.get("LARAVEL_URL", "https://ariatyx-gaming-production-2393.up.railway.app")

BASE_URL = "https://api.lootlocker.io"
LL_VERSION = "2021-03-01"


def fetch_leaderboard():
    try:
        auth_res = requests.post(
            f"{BASE_URL}/server/session",
            json={"game_version": "1.0.0.0"},
            headers={
                "Content-Type": "application/json",
                "LL-Version": LL_VERSION,
                "x-server-key": SERVER_API_KEY,
            },
            timeout=10,
        )

        if auth_res.status_code != 200:
            return None, f"Authentication failed: {auth_res.text}"

        token = auth_res.json().get("token")
        if not token:
            return None, "No token received from LootLocker."

        data_res = requests.get(
            f"{BASE_URL}/server/leaderboards/{LEADERBOARD_KEY}/list?count=100&after=0",
            headers={
                "LL-Version": LL_VERSION,
                "x-auth-token": token,
            },
            timeout=10,
        )

        if data_res.status_code != 200:
            return None, f"Leaderboard fetch failed: {data_res.text}"

        items = data_res.json().get("items", [])

        with open(CSV_FILE, "w", newline="", encoding="utf-8") as file:
            writer = csv.writer(file)
            writer.writerow(["Rank", "Player Name", "Score", "Member ID", "Public UID"])

            for item in items:
                player = item.get("player", {})
                writer.writerow([
                    item.get("rank"),
                    player.get("name") or "Unknown Player",
                    item.get("score") or 0,
                    item.get("member_id"),
                    player.get("public_uid"),
                ])

        return items, None

    except Exception as e:
        return None, str(e)


@app.route("/")
def dashboard():
    items, error = fetch_leaderboard()

    if error:
        return f"""
        <h1>LootLocker Error</h1>
        <p>{error}</p>
        <a href="{LARAVEL_URL}/launcher">Return to Launcher</a>
        """

    if not os.path.exists(CSV_FILE):
        return "<h1>No leaderboard data found.</h1>"

    df = pd.read_csv(CSV_FILE)

    if df.empty:
        return "<h1>Leaderboard is empty.</h1>"

    df = df.sort_values("Rank")
    players = df.to_dict(orient="records")

    html = """
    <!DOCTYPE html>
    <html>
    <head>
        <title>BulletDrop Analytics</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            body {
                background: #000;
                color: white;
                font-family: Arial, sans-serif;
                padding: 30px;
            }
            h1 {
                color: #FFD700;
            }
            .cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .card {
                background: #111;
                border: 1px solid #FFD700;
                border-radius: 12px;
                padding: 20px;
                text-align: center;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 30px;
            }
            th, td {
                border: 1px solid #444;
                padding: 10px;
            }
            th {
                background: #222;
                color: #FFD700;
            }
            a, button {
                background: #FFD700;
                color: black;
                padding: 10px 18px;
                border-radius: 8px;
                text-decoration: none;
                border: none;
                font-weight: bold;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <h1>🏆 BulletDrop Leaderboard Analytics</h1>

        <a href="{{ laravel_url }}/launcher">← Return to Launcher</a>
        <button onclick="location.reload()">Refresh</button>

        <div class="cards">
            <div class="card">
                <h2>{{ total_players }}</h2>
                <p>Total Players</p>
            </div>
            <div class="card">
                <h2>{{ highest_score }}</h2>
                <p>Highest Score</p>
            </div>
            <div class="card">
                <h2>{{ top_player }}</h2>
                <p>Top Player</p>
            </div>
            <div class="card">
                <h2>{{ top_10_avg }}</h2>
                <p>Average Score</p>
            </div>
        </div>

        <canvas id="leaderboardChart"></canvas>

        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Player Name</th>
                    <th>Score</th>
                    <th>Member ID</th>
                    <th>Public UID</th>
                </tr>
            </thead>
            <tbody>
                {% for player in players %}
                <tr>
                    <td>#{{ player["Rank"] }}</td>
                    <td>{{ player["Player Name"] }}</td>
                    <td>{{ player["Score"] }}</td>
                    <td>{{ player["Member ID"] }}</td>
                    <td>{{ player["Public UID"] }}</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>

        <script>
            const playerNames = {{ player_names | safe }};
            const playerScores = {{ player_scores | safe }};

            new Chart(document.getElementById("leaderboardChart"), {
                type: "bar",
                data: {
                    labels: playerNames,
                    datasets: [{
                        label: "Score",
                        data: playerScores
                    }]
                }
            });
        </script>
    </body>
    </html>
    """

    return render_template_string(
        html,
        players=players,
        player_names=json.dumps(df["Player Name"].tolist()),
        player_scores=json.dumps(df["Score"].tolist()),
        total_players=len(df),
        highest_score=int(df["Score"].max()),
        top_player=df.iloc[0]["Player Name"],
        top_10_avg=int(df["Score"].mean()),
        laravel_url=LARAVEL_URL,
    )


@app.route("/analytics")
def analytics():
    return dashboard()


@app.route("/health")
def health():
    return "OK", 200


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8080))
    app.run(host="0.0.0.0", port=port)