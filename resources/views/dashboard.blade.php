<x-app-layout>
    <style>
        .launcher-wrapper {
            /* Adjusted height to fit standard header */
            min-height: calc(100vh - 64px); 
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            transition: background-image 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Bullet Drop as default */
            background-image: url('{{ asset("images/bulletdropnologo.png") }}');
        }

        .game-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
            padding: 50px;
            position: absolute;
            bottom: 30px;
            width: 100%;
            z-index: 10;
        }

        .game-card {
            position: relative;
            width: 280px;
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .game-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 230, 0, 0.5);
        }

        .game-card.active {
            box-shadow: 0 0 25px rgba(255, 230, 0, 0.8);
            border: 2px solid #ffe600;
        }

        .game-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .game-card:hover img { transform: scale(1.1); }

        .overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.6);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .game-card:hover .overlay { opacity: 1; }

        .overlay p {
            color: #000;
            font-weight: 900;
            font-size: 14px;
            background: #ffe600;
            padding: 10px 20px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .launcher-wrapper::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle, transparent 40%, rgba(0,0,0,0.4) 100%);
            pointer-events: none;
        }
    </style>

    <div class="launcher-wrapper" id="launcherBody">
        <div class="game-container">
            <a href="{{ route('launcher') }}" class="block">
    <div class="game-card active" data-bg="{{ asset('images/bulletdropnologo.png') }}">
        <img src="{{ asset('images/bulletdropwlogo.png') }}" alt="Bullet Drop">
        <div class="overlay">
            <p>Launch Game</p>
        </div>
    </div>
</a>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll(".game-card");
            const launcherBody = document.getElementById("launcherBody");

            cards.forEach(card => {
                card.addEventListener("mouseenter", () => {
                    const bg = card.getAttribute("data-bg");
                    launcherBody.style.backgroundImage = `url('${bg}')`;
                    cards.forEach(c => c.classList.remove("active"));
                    card.classList.add("active");
                });
            });
        });
    </script>
</x-app-layout>
