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
    median_score = df["Score"].median()

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
                background: linear-gradient(135deg, #f0f4f8 0%, #e8edf5 100%);
                font-family: 'Barlow', sans-serif;
                color: #1a1a2e;
                min-height: 100vh;
                overflow-x: auto;
            }

            /* Subtle gaming pattern */
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgdmlld0JveD0iMCAwIDYwIDYwIj48cGF0aCBkPSJNMzAgMTBhMjAgMjAgMCAwIDEgMCA0MCAyMCAyMCAwIDAgMSAwLTQweiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZmZkYzAwIiBzdHJva2Utd2lkdGg9IjEiIHN0cm9rZS1vcGFjaXR5PSIwLjA1Ii8+PC9zdmc+');
                background-repeat: repeat;
                opacity: 0.4;
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

            /* Header with gaming style */
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 40px;
                padding: 20px 30px;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(20px);
                border-radius: 16px;
                border: 2px solid #FFD700;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            }

            .title-section h1 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: clamp(32px, 5vw, 48px);
                font-weight: 900;
                text-transform: uppercase;
                background: linear-gradient(135deg, #1a1a2e 0%, #FFD700 50%, #1a1a2e 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                letter-spacing: -1px;
            }

            .title-section p {
                color: #666;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
                font-weight: 600;
            }

            /* Return Button - Gaming style */
            .return-btn {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                background: linear-gradient(135deg, #FFD700, #FFA500);
                border: 2px solid #fff;
                border-radius: 40px;
                padding: 10px 28px;
                color: #1a1a2e;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
                font-weight: 800;
                text-transform: uppercase;
                text-decoration: none;
                letter-spacing: 1.5px;
                cursor: pointer;
                transition: all 0.25s ease;
                box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
            }

            .return-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(255, 215, 0, 0.5);
                background: linear-gradient(135deg, #FFE44D, #FFB347);
            }

            /* Stats Cards - Gaming style */
            .cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 24px;
                margin-bottom: 40px;
            }

            .card {
                background: white;
                border-radius: 16px;
                padding: 28px 24px;
                text-align: center;
                transition: all 0.3s ease;
                border: 2px solid #FFD700;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                position: relative;
                overflow: hidden;
            }

            .card::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.1), transparent);
                transition: left 0.5s ease;
            }

            .card:hover::before {
                left: 100%;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 24px rgba(255, 215, 0, 0.2);
                border-color: #FFC800;
            }

            .card h2 {
                font-family: 'Oswald', sans-serif;
                font-size: 42px;
                font-weight: 700;
                color: #FFD700;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 12px;
            }

            .card p {
                color: #555;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 14px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            /* Chart Container */
            .chart-container {
                background: white;
                border-radius: 16px;
                padding: 28px;
                margin-bottom: 40px;
                border: 2px solid #FFD700;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                transition: 0.3s;
            }

            .chart-container:hover {
                box-shadow: 0 8px 24px rgba(255, 215, 0, 0.15);
                border-color: #FFC800;
            }

            .chart-container h3 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 24px;
                font-weight: 800;
                margin-bottom: 20px;
                color: #FFD700;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
                letter-spacing: 1px;
            }

            canvas {
                max-height: 400px;
            }

            /* Table Container - Gaming style */
            .table-container {
                background: white;
                border-radius: 16px;
                padding: 28px;
                overflow-x: auto;
                border: 2px solid #FFD700;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                transition: 0.3s;
            }

            .table-container:hover {
                box-shadow: 0 8px 24px rgba(255, 215, 0, 0.15);
                border-color: #FFC800;
            }

            .table-container h3 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 24px;
                font-weight: 800;
                margin-bottom: 20px;
                color: #FFD700;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
                letter-spacing: 1px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-family: 'Barlow', sans-serif;
            }

            th {
                text-align: left;
                padding: 16px 12px;
                background: linear-gradient(135deg, #f5f5f5, #e8e8e8);
                font-family: 'Barlow Condensed', sans-serif;
                font-weight: 800;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #FFD700;
                border-bottom: 3px solid #FFD700;
            }

            td {
                padding: 14px 12px;
                border-bottom: 1px solid #e0e0e0;
                color: #333;
                font-size: 14px;
                font-weight: 500;
            }

            tr:hover td {
                background: rgba(255, 215, 0, 0.08);
            }

            .rank-cell {
                font-weight: 900;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
            }

            .rank-1 {
                color: #FFD700;
                text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
            }

            .rank-2 {
                color: #C0C0C0;
            }

            .rank-3 {
                color: #CD7F32;
            }

            .score-cell {
                font-weight: 800;
                color: #FFD700;
                font-size: 16px;
            }

            .refresh-btn {
                background: linear-gradient(135deg, #FFD700, #FFA500);
                border: 2px solid #fff;
                border-radius: 40px;
                padding: 8px 24px;
                color: #1a1a2e;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 14px;
                font-weight: 800;
                text-transform: uppercase;
                cursor: pointer;
                transition: all 0.2s ease;
                margin-left: 20px;
                box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
            }

            .refresh-btn:hover {
                transform: scale(1.02);
                box-shadow: 0 4px 12px rgba(255, 215, 0, 0.5);
                background: linear-gradient(135deg, #FFE44D, #FFB347);
            }

            /* Scrollbar styling */
            ::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }

            ::-webkit-scrollbar-track {
                background: #f0f0f0;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb {
                background: #FFD700;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #FFA500;
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
                    font-size: 13px;
                }
                th, td {
                    padding: 10px 8px;
                    font-size: 12px;
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
                        backgroundColor: 'rgba(255, 215, 0, 0.7)',
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
                                color: '#333',
                                font: { family: 'Barlow Condensed', size: 14, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.9)',
                            titleColor: '#FFD700',
                            bodyColor: '#fff',
                            borderColor: '#FFD700',
                            borderWidth: 2,
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
                                color: '#555',
                                font: { family: 'Barlow', size: 11 },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        y: {
                            ticks: {
                                color: '#555',
                                font: { family: 'Barlow', size: 12 }
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'SCORE',
                                color: '#FFD700',
                                font: { family: 'Barlow Condensed', size: 14, weight: 'bold' }
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