from flask import Flask, render_template_string, jsonify, redirect
import requests
import csv
import pandas as pd
import os


import os
from flask import Flask

app = Flask(__name__)

@app.route("/")
def home():
    return "Python Analytics Working!"

@app.route("/analytics")
def analytics():
    return "Analytics Route Working!"

if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8080))
    app.run(host="0.0.0.0", port=port)


    
# In analytics/index.py
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CSV_FILE = os.path.join(BASE_DIR, "leaderboard_analytics.csv")
app = Flask(__name__)

SERVER_API_KEY = "dev_9bfb5807a5f64ea78d225fdfe6de70a2"
LEADERBOARD_KEY = "main_leaderboard"

BASE_URL = "https://api.lootlocker.io"
LL_VERSION = "2021-03-01"


def fetch_leaderboard():
    auth_url = f"{BASE_URL}/server/session"

    auth_headers = {
        "Content-Type": "application/json",
        "LL-Version": LL_VERSION,
        "x-server-key": SERVER_API_KEY
    }

    auth_payload = {
        "game_version": "1.0.0.0"
    }

    try:
        auth_res = requests.post(
            auth_url,
            json=auth_payload,
            headers=auth_headers,
            timeout=10
        )

        if auth_res.status_code != 200:
            return None, auth_res.text

        token = auth_res.json().get("token")

        if not token:
            return None, "No token received from LootLocker."

        leaderboard_url = f"{BASE_URL}/server/leaderboards/{LEADERBOARD_KEY}/list?count=100&after=0"

        leaderboard_headers = {
            "LL-Version": LL_VERSION,
            "x-auth-token": token
        }

        data_res = requests.get(
            leaderboard_url,
            headers=leaderboard_headers,
            timeout=10
        )

        if data_res.status_code != 200:
            return None, data_res.text

        items = data_res.json().get("items", [])

        with open(CSV_FILE, mode="w", newline="", encoding="utf-8") as file:
            writer = csv.writer(file)

            writer.writerow([
                "Rank",
                "Player Name",
                "Score",
                "Member ID",
                "Player ID",
                "Public UID",
                "ULID",
                "Metadata"
            ])

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

    except requests.exceptions.ConnectionError:
        return None, "Cannot connect to LootLocker. Check your internet, DNS, or firewall."

    except requests.exceptions.Timeout:
        return None, "LootLocker request timed out."

    except requests.exceptions.RequestException as e:
        return None, str(e)


@app.route("/")
def dashboard():
    items, error = fetch_leaderboard()

    if error:
        return f"""
        <h1>LootLocker Connection Error</h1>
        <p>{error}</p>
        <p>Check your internet, DNS, API key, and leaderboard key.</p>
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
                background: #000000;
                font-family: 'Barlow', sans-serif;
                color: #e8e8f0;
                min-height: 100vh;
                overflow-x: auto;
            }

            /* Scanlines effect */
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);
                pointer-events: none;
                z-index: 100;
            }

            /* Animated background gradient */
            .bg-gradient {
                position: fixed;
                inset: 0;
                background: radial-gradient(ellipse at 30% 40%, rgba(255,215,0,0.08) 0%, rgba(0,0,0,0.95) 70%);
                z-index: -2;
            }

            .bg-noise {
                position: fixed;
                inset: 0;
                background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIj48ZmlsdGVyIGlkPSJmIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iLjciIG51bU9jdGF2ZXM9IjMiLz48L2ZpbHRlcj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWx0ZXI9InVybCgjZikiIG9wYWNpdHk9IjAuMDYiLz48L3N2Zz4=');
                background-repeat: repeat;
                opacity: 0.3;
                pointer-events: none;
                z-index: -1;
            }

            .container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 30px 24px 60px;
                position: relative;
                z-index: 1;
            }

            /* Header with return button */
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 40px;
                padding-bottom: 20px;
                border-bottom: 1px solid rgba(255,215,0,0.3);
            }

            .title-section h1 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: clamp(32px, 5vw, 48px);
                font-weight: 900;
                text-transform: uppercase;
                background: linear-gradient(135deg, #fff 0%, #FFD700 40%, #FF4DA6 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                letter-spacing: -1px;
            }

            .title-section p {
                color: rgba(255,255,255,0.6);
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
                letter-spacing: 1px;
            }

            /* Return Button - Same style as launcher */
            .return-btn {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,215,0,0.4);
                border-radius: 40px;
                padding: 10px 28px;
                color: #FFD700;
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 16px;
                font-weight: 700;
                text-transform: uppercase;
                text-decoration: none;
                letter-spacing: 1.5px;
                cursor: pointer;
                transition: all 0.25s ease;
                backdrop-filter: blur(8px);
            }

            .return-btn:hover {
                background: linear-gradient(135deg, rgba(255,215,0,0.15), rgba(255,77,166,0.1));
                border-color: #FFD700;
                transform: translateX(-3px);
                box-shadow: 0 0 20px rgba(255,215,0,0.3);
            }

            /* Stats Cards - Glowing effect */
            .cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 40px;
            }

            .card {
                background: rgba(10,10,15,0.7);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,215,0,0.2);
                border-radius: 16px;
                padding: 24px 20px;
                text-align: center;
                transition: all 0.3s ease;
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
                background: linear-gradient(90deg, transparent, rgba(255,215,0,0.1), transparent);
                transition: left 0.5s ease;
            }

            .card:hover::before {
                left: 100%;
            }

            .card:hover {
                border-color: rgba(255,215,0,0.5);
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            }

            .card h2 {
                font-family: 'Oswald', sans-serif;
                font-size: 36px;
                font-weight: 700;
                background: linear-gradient(135deg, #FFD700, #FF4DA6);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin-bottom: 8px;
            }

            .card p {
                color: rgba(255,255,255,0.7);
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            /* Chart Container */
            .chart-container {
                background: rgba(10,10,15,0.7);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,215,0,0.2);
                border-radius: 20px;
                padding: 25px;
                margin-bottom: 40px;
                transition: 0.3s;
            }

            .chart-container:hover {
                border-color: rgba(255,215,0,0.4);
                box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            }

            .chart-container h3 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 20px;
                color: #FFD700;
                letter-spacing: 1px;
            }

            canvas {
                max-height: 400px;
            }

            /* Table Container - Cyberpunk style */
            .table-container {
                background: rgba(10,10,15,0.7);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,215,0,0.2);
                border-radius: 20px;
                padding: 25px;
                overflow-x: auto;
                transition: 0.3s;
            }

            .table-container:hover {
                border-color: rgba(255,215,0,0.4);
            }

            .table-container h3 {
                font-family: 'Barlow Condensed', sans-serif;
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 20px;
                color: #FFD700;
                letter-spacing: 1px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-family: 'Barlow', sans-serif;
            }

            th {
                text-align: left;
                padding: 14px 12px;
                background: rgba(255,215,0,0.1);
                font-family: 'Barlow Condensed', sans-serif;
                font-weight: 700;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #FFD700;
                border-bottom: 2px solid rgba(255,215,0,0.3);
            }

            td {
                padding: 12px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
                color: #e8e8f0;
                font-size: 14px;
            }

            tr:hover td {
                background: rgba(255,215,0,0.05);
            }

            .rank-cell {
                font-weight: 800;
                font-family: 'Barlow Condensed', sans-serif;
            }

            .rank-1 {
                color: #FFD700;
                text-shadow: 0 0 10px rgba(255,215,0,0.5);
            }

            .rank-2 {
                color: #C0C0C0;
            }

            .rank-3 {
                color: #CD7F32;
            }

            .score-cell {
                font-weight: 700;
                color: #FFD700;
            }

            .refresh-btn {
                background: rgba(255,215,0,0.15);
                border: 1px solid rgba(255,215,0,0.4);
                border-radius: 30px;
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
                background: rgba(255,215,0,0.3);
                transform: scale(1.02);
            }

            /* Scrollbar styling */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #111;
            }

            ::-webkit-scrollbar-thumb {
                background: #FFD700;
                border-radius: 4px;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .container {
                    padding: 20px 16px;
                }
                .header {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .return-btn {
                    padding: 6px 20px;
                    font-size: 13px;
                }
                th, td {
                    padding: 8px;
                    font-size: 11px;
                }
                .card h2 {
                    font-size: 28px;
                }
            }
        </style>
    </head>

    <body>
        <div class="bg-gradient"></div>
        <div class="bg-noise"></div>

        <div class="container">
            <div class="header">
                <div class="title-section">
                    <h1>🏆 LEADERBOARD ANALYTICS</h1>
                    <p>LootLocker | Real-time Score Tracking</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <button class="refresh-btn" onclick="location.reload()">⟳ REFRESH</button>
                    <a href="http://127.0.0.1:8000/launcher" class="return-btn">
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
                            <td class="score-cell">{{ player["Score"] }}</td>
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
                                color: '#e8e8f0',
                                font: { family: 'Barlow Condensed', size: 14 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.9)',
                            titleColor: '#FFD700',
                            bodyColor: '#e8e8f0',
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
                                color: '#e8e8f0',
                                font: { family: 'Barlow', size: 11 },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: { color: 'rgba(255,255,255,0.05)' }
                        },
                        y: {
                            ticks: {
                                color: '#e8e8f0',
                                font: { family: 'Barlow', size: 12 }
                            },
                            grid: { color: 'rgba(255,255,255,0.05)' },
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
        player_names=df["Player Name"].tolist(),
        player_scores=df["Score"].tolist(),
        total_players=len(df),
        highest_score=df["Score"].max(),
        top_player=df.iloc[0]["Player Name"],
        top_10_avg=top_10_avg
    )


if __name__ == "__main__":
    app.run(debug=True)