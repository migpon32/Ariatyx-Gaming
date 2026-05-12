<!DOCTYPE html>
<html lang="en">
<head>

    <!-- 🔥 FAVICON (WAR BIRD FOR LAUNCHER) -->
    <link rel="icon" type="image/png" href="{{ asset('images/War_Bird.png') }}">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <title>Launcher | Ariatyx Gaming</title>

    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&family=Oswald:wght@700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --yellow: #FFD700;
            --pink: #FF4DA6;
            --dark: #000000;
            --text: #e8e8f0;
        }

        html, body {
            width: 100%; height: 100%;
            background: var(--dark);
            font-family: 'Barlow', sans-serif;
            color: var(--text);
            overflow: hidden;
        }

        .launcher {
            width: 100vw; height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── BACKGROUND ── */
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('{{ asset("images/bulletdropnologo.png") }}');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            z-index: 0;
        }
        .hero-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.1) 100%);
            z-index: 2;
        }

        /* ── BRANDING LOGO (Ariatyx Fix) ── */
        .branding-container {
            position: absolute;
            top: 28px;
            left: 28px;
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            animation: fadeSlideIn 0.8s ease both;
        }
        .studio-logo {
            height: 24px; /* Matches text height perfectly */
            width: auto;
        }
        .studio-name {
            color: white;
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 18px;
            letter-spacing: -0.5px;
        }

        /* ── BACK BUTTON ── */
        .back-button {
            position: absolute;
            top: 28px;
            right: 28px;
            z-index: 30;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            padding: 8px 20px;
            border-radius: 4px;
            transition: 0.2s;
        }
        .back-button:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ── BANNER TITLE ANIMATION ── */
        .banner-title {
            position: absolute;
            left: 8%;
            top: 50%;
            transform: translateY(-50%);
            z-index: 15;
            animation: fadeSlideIn 0.7s 0.2s ease both;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(-40%); }
            to { opacity: 1; transform: translateY(-50%); }
        }

        .banner-main {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(48px, 7vw, 95px);
            font-weight: 900;
            text-transform: uppercase;
            line-height: 0.92;
        }
        .banner-main .line1 {
            display: block;
            color: transparent;
            -webkit-text-stroke: 2px rgba(255,255,255,0.9);
            font-style: italic;
        }
        .banner-main .line2 {
            display: block;
            background: linear-gradient(135deg, #fff 0%, #FFD700 40%, #FF4DA6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ── START BUTTON ── */
        .start-wrapper {
            position: absolute;
            right: 8%;
            bottom: 12%;
            z-index: 15;
            animation: fadeSlideUp 0.7s 0.4s ease both;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 16px;
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-start {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--yellow);
            color: #000;
            border: none;
            padding: 0 40px 0 15px;
            height: 60px;
            border-radius: 40px;
            cursor: pointer;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(255,215,0,0.3);
            transition: 0.2s;
        }
        .btn-start:hover { transform: scale(1.05); }

        .play-icon {
            width: 38px; height: 38px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .start-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 5px;
            background: rgba(0,0,0,0.4);
            width: 0%;
        }

        .scanlines {
            position: absolute; inset: 0; z-index: 25;
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);
            pointer-events: none;
        }
        
        /* FIXED LEADERBOARD BUTTON - Preserving all original design intentions */
        .btn-leaderboard {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: auto;
            min-width: 220px;
            height: 50px;
            border-radius: 40px;
            border: 1px solid rgba(255,215,0,0.5);
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            color: var(--yellow);
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            padding: 0 24px;
        }
        
        /* Trophy icon styling */
        .leaderboard-icon {
            font-size: 22px;
            filter: drop-shadow(0 0 2px rgba(255,215,0,0.5));
        }
        
        /* Hover effect - preserves all original design language */
        .btn-leaderboard:hover {
            background: linear-gradient(135deg, rgba(255,215,0,0.2), rgba(255,77,166,0.15));
            border-color: var(--yellow);
            color: #FFD700;
            transform: scale(1.05);
            box-shadow: 0 0 18px rgba(255,215,0,0.4);
        }
        
        /* Active/click feedback */
        .btn-leaderboard:active {
            transform: scale(0.98);
        }
        
        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .start-wrapper {
                right: 5%;
                bottom: 8%;
                gap: 12px;
            }
            .btn-leaderboard {
                min-width: 190px;
                height: 46px;
                font-size: 16px;
                padding: 0 18px;
            }
            .leaderboard-icon {
                font-size: 20px;
            }
            .btn-start {
                height: 54px;
                font-size: 20px;
                padding: 0 30px 0 12px;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 480px) {
            .btn-leaderboard {
                min-width: 170px;
                height: 42px;
                font-size: 14px;
                gap: 8px;
            }
            .leaderboard-icon {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="launcher">
    <div class="hero-bg"></div>
    <div class="scanlines"></div>

    <a href="{{ route('dashboard') }}" class="branding-container">
        <img src="{{ asset('images/logo.png') }}" alt="Ariatyx Logo" class="studio-logo">
        <span class="studio-name">ARIATYX GAMES</span>
    </a>
    
    <button class="back-button" onclick="window.location.href='{{ route('dashboard') }}'">
        Exit Launcher
    </button>

    <div class="banner-title">
        <div class="banner-main">
            <span class="line1">BULLETDROP</span>
            <span class="line2">FLY AND SHOOT</span>
        </div>
        <div class="banner-subtitle">TO SURVIVE</div>
    </div>

    <div class="start-wrapper">
        <button class="btn-start" id="startBtn">
            <div class="play-icon">▶</div>
            <span id="btnText">Launch Game</span>
            <div class="start-progress" id="progressBar"></div>
        </button>

        <!-- FIXED LEADERBOARD BUTTON -->
        <!-- 
            Original code had a broken div with Tailwind classes that didn't exist.
            This replacement maintains the exact same visual language, hover effects,
            and design consistency while being fully functional.
        -->
        <a href="https://zesty-nature-production-e1e4.up.railway.app" 
           target="_blank" 
           class="btn-leaderboard"
           id="leaderboardBtn">
            <span class="leaderboard-icon">🏆</span>
            <span>VIEW LIVE LEADERBOARD</span>
        </a>
    </div>
</div>

<script>
    const startBtn = document.getElementById('startBtn');
    const progressBar = document.getElementById('progressBar');
    const btnText = document.getElementById('btnText');
    let isLaunching = false;

    startBtn.addEventListener('click', () => {
        if (isLaunching) return;
        isLaunching = true;
        
        btnText.innerText = "Launching...";
        startBtn.style.opacity = "0.8";
        
        let width = 0;
        const interval = setInterval(() => {
            width += 2.5; 
            progressBar.style.width = width + "%";
            
            if (width >= 100) {
                clearInterval(interval);
                // The actual redirect animation
                window.location.href = "{{ route('game.play') }}"; 
            }
        }, 35); 
    });
    
    // Optional: Add a subtle ripple/glow effect on leaderboard button click (just for extra polish)
    const leaderboardBtn = document.getElementById('leaderboardBtn');
    if (leaderboardBtn) {
        leaderboardBtn.addEventListener('click', function(e) {
            // Create a tiny flash effect to acknowledge the click without breaking functionality
            const originalBorder = this.style.borderColor;
            this.style.borderColor = '#FFD700';
            this.style.transition = 'border-color 0.1s ease';
            setTimeout(() => {
                if (this) this.style.borderColor = '';
            }, 120);
        });
    }
</script>
</body>
</html>
