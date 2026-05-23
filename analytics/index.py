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
            writer.writerow(["Rank", "Player Name", "Score", "Member ID", "Player ID", "Public UID", "ULID", "Metadata"])

            for item in items:
                player = item.get("player", {})
                writer.writerow([
                    item.get("rank"),
                    player.get("name") or "Unknown Player",
                    item.get("score") or 0,
                    item.get("member_id"),
                    player.get("id"),
                    player.get("public_uid"),
                    player.get("ulid"),
                    item.get("metadata")
                ])

        return items, None

    except Exception as e:
        return None, str(e)


@app.route("/")
def dashboard():
    items, error = fetch_leaderboard()

    if error:
        return f"""
        <h1>LootLocker Connection Error</h1>
        <p>{error}</p>
        <p>Check your internet, DNS, API key, and leaderboard key.</p>
        <a href="{LARAVEL_URL}/launcher">Return to Launcher</a>
        """

    if not os.path.exists(CSV_FILE):
        return "<h1>No leaderboard data found.</h1>"

    df = pd.read_csv(CSV_FILE)

    if df.empty:
        return "<h1>Leaderboard is empty.</h1>"

    df = df.sort_values("Rank")
    players = df.to_dict(orient="records")

    # Calculate additional stats
    top_10_avg = df.head(10)["Score"].mean() if len(df) >= 10 else df["Score"].mean()

    html = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <title>BulletDrop - Leaderboard Analytics</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&family=Oswald:wght@700&display=swap" rel="stylesheet">
        
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                background: linear-gradient(135deg, #f5f7fa 0%, #e9eef3 100%);
                font-family: 'Barlow', sans-serif;
                color: #1a1f2e;
                min-height: 100vh;
                overflow-x: auto;
            }

            /* Subtle gaming grid pattern */
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: 
                    linear-gradient(rgba(255, 215, 0, 0.08) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 215, 0, 0.08) 1px, transparent 1px);
                background-size: 30px 30px;
                pointer-events: none;
                z-index: 0;
            }

            .container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 30px 24px 60px;
                position: relative;
                z-index: 1;
            }

            /* Header with subtle gold border */
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 40px;
                padding: 20px 30px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                border: 1px solid rgba(255, 215, 0, 0.3);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            }

            .title-section h1 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: clamp(32px, 5vw, 48px);
                font-weight: 900;
                text-transform: uppercase;
                color: #1a1f2e;
                letter-spacing: -0.5px;
                text-shadow: 1px 1px 0 rgba(255, 215, 0, 0.3);
            }

            .title-section p {
                color: #5a6e8a;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
                font-weight: 500;
            }

            /* Return Button */
            .return-btn {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                background: #1a1f2e;
                border: 2px solid #FFD700;
                border-radius: 40px;
                padding: 10px 28px;
                color: #FFD700;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
                font-weight: 700;
                text-transform: uppercase;
                text-decoration: none;
                letter-spacing: 1px;
                cursor: pointer;
                transition: all 0.25s ease;
            }

            .return-btn:hover {
                transform: translateY(-2px);
                background: #FFD700;
                color: #1a1f2e;
                box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
            }

            /* Stats Cards */
            .cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 24px;
                margin-bottom: 40px;
            }

            .card {
                background: white;
                border-radius: 20px;
                padding: 28px 24px;
                text-align: center;
                transition: all 0.3s ease;
                border: 1px solid rgba(255, 215, 0, 0.3);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            }

            .card:hover {
                transform: translateY(-4px);
                border-color: #FFD700;
                box-shadow: 0 8px 24px rgba(255, 215, 0, 0.12);
            }

            .card h2 {
                font-family: 'Oswald', sans-serif;
                font-size: 42px;
                font-weight: 700;
                color: #1a1f2e;
                margin-bottom: 12px;
                text-shadow: 0.5px 0.5px 0 #FFD700;
            }

            .card p {
                color: #5a6e8a;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 14px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            /* Chart Container */
            .chart-container {
                background: white;
                border-radius: 20px;
                padding: 28px;
                margin-bottom: 40px;
                border: 1px solid rgba(255, 215, 0, 0.3);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                transition: 0.3s;
            }

            .chart-container:hover {
                border-color: #FFD700;
                box-shadow: 0 8px 24px rgba(255, 215, 0, 0.12);
            }

            .chart-container h3 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 24px;
                font-weight: 800;
                margin-bottom: 20px;
                color: #1a1f2e;
                letter-spacing: 0.5px;
                text-shadow: 0.5px 0.5px 0 rgba(255, 215, 0, 0.3);
            }

            canvas {
                max-height: 400px;
            }

            /* Table Container */
            .table-container {
                background: white;
                border-radius: 20px;
                padding: 28px;
                overflow-x: auto;
                border: 1px solid rgba(255, 215, 0, 0.3);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                transition: 0.3s;
            }

            .table-container:hover {
                border-color: #FFD700;
                box-shadow: 0 8px 24px rgba(255, 215, 0, 0.12);
            }

            .table-container h3 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 24px;
                font-weight: 800;
                margin-bottom: 20px;
                color: #1a1f2e;
                letter-spacing: 0.5px;
                text-shadow: 0.5px 0.5px 0 rgba(255, 215, 0, 0.3);
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-family: 'Barlow', sans-serif;
            }

            th {
                text-align: left;
                padding: 16px 12px;
                background: #f8fafc;
                font-family: 'Barlow Condensed', sans-serif;
                font-weight: 800;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #1a1f2e;
                border-bottom: 2px solid #FFD700;
            }

            td {
                padding: 14px 12px;
                border-bottom: 1px solid #e2e8f0;
                color: #334155;
                font-size: 14px;
                font-weight: 500;
            }

            tr:hover td {
                background: rgba(255, 215, 0, 0.05);
            }

            .rank-cell {
                font-weight: 900;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
            }

            .rank-1 {
                color: #1a1f2e;
                text-shadow: 0.5px 0.5px 0 #FFD700;
                position: relative;
            }

            .rank-2 {
                color: #475569;
                text-shadow: 0.5px 0.5px 0 #cbd5e1;
            }

            .rank-3 {
                color: #78350f;
                text-shadow: 0.5px 0.5px 0 #fbbf24;
            }

            .score-cell {
                font-weight: 800;
                color: #1a1f2e;
                text-shadow: 0.5px 0.5px 0 #FFD700;
                font-size: 16px;
            }

            .refresh-btn {
                background: #1a1f2e;
                border: 2px solid #FFD700;
                border-radius: 40px;
                padding: 8px 24px;
                color: #FFD700;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 14px;
                font-weight: 700;
                text-transform: uppercase;
                cursor: pointer;
                transition: all 0.2s ease;
                margin-left: 20px;
            }

            .refresh-btn:hover {
                background: #FFD700;
                color: #1a1f2e;
                transform: scale(1.02);
            }

            /* Scrollbar styling */
            ::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #FFD700;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .container {
                    padding: 20px 16px;
                }
                .header {
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 20px;
                }
                .return-btn, .refresh-btn {
                    padding: 6px 20px;
                    font-size: 12px;
                }
                th, td {
                    padding: 10px 8px;
                    font-size: 11px;
                }
                .card h2 {
                    font-size: 32px;
                }
                .card {
                    padding: 20px;
                }
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <div class="title-section">
                    <h1>🏆 LEADERBOARD ANALYTICS</h1>
                    <p>LootLocker | Real-time Score Tracking | Gaming Stats</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <button class="refresh-btn" onclick="location.reload()">⟳ REFRESH</button>
                    <a href="{{ laravel_url }}/launcher" class="return-btn">
                        <span>←</span>
                        <span>RETURN TO LAUNCHER</span>
                    </a>
                </div>
            </div>

            <div class="cards">
                <div class="card">
                    <h2>{{ total_players }}</h2>
                    <p>Total Fighters</p>
                </div>
                <div class="card">
                    <h2>{{ highest_score }}</h2>
                    <p>Top Score</p>
                </div>
                <div class="card">
                    <h2>{{ top_player }}</h2>
                    <p>Current Champion</p>
                </div>
                <div class="card">
                    <h2>{{ "%.0f"|format(top_10_avg) }}</h2>
                    <p>Top 10 Avg Score</p>
                </div>
            </div>

            <div class="chart-container">
                <h3>📊 SCORE DISTRIBUTION</h3>
                <canvas id="leaderboardChart"></canvas>
            </div>

            <div class="table-container">
                <h3>📋 PLAYER RANKINGS</h3>
                <table>
                    <thead>
                        <tr>
                            <th>RANK</th>
                            <th>PLAYER NAME</th>
                            <th>SCORE</th>
                            <th>MEMBER ID</th>
                            <th>PUBLIC UID</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for player in players %}
                        <tr>
                            <td class="rank-cell 
                                {% if player['Rank'] == 1 %}rank-1
                                {% elif player['Rank'] == 2 %}rank-2
                                {% elif player['Rank'] == 3 %}rank-3
                                {% endif %}">
                                #{{ player["Rank"] }}
                            </td>
                            <td><strong>{{ player["Player Name"] }}</strong></td>
                            <td class="score-cell">{{ "{:,.0f}".format(player["Score"]) }}</td>
                            <td>{{ player["Member ID"] }}</td>
                            <td>{{ player["Public UID"] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            const playerNames = {{ player_names | safe }};
            const playerScores = {{ player_scores | safe }};

            const ctx = document.getElementById("leaderboardChart");

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: playerNames,
                    datasets: [{
                        label: 'Score',
                        data: playerScores,
                        backgroundColor: 'rgba(26, 31, 46, 0.7)',
                        borderColor: '#FFD700',
                        borderWidth: 2,
                        borderRadius: 6,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#1a1f2e',
                                font: { family: 'Barlow Condensed', size: 13, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1a1f2e',
                            titleColor: '#FFD700',
                            bodyColor: '#fff',
                            borderColor: '#FFD700',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return `Score: ${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#475569',
                                font: { family: 'Barlow', size: 11 },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        y: {
                            ticks: {
                                color: '#475569',
                                font: { family: 'Barlow', size: 12 }
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'SCORE',
                                color: '#1a1f2e',
                                font: { family: 'Barlow Condensed', size: 13, weight: 'bold' }
                            }
                        }
                    }
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
        highest_score=f"{int(df['Score'].max()):,}",
        top_player=df.iloc[0]["Player Name"],
        top_10_avg=top_10_avg,
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