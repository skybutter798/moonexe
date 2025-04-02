<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to MoonExe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:600" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <!-- Favicon and Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        body {
            background-color: white;
            color: black;
            text-align: center;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .orbit {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 5px;
        }
        .planet {
            width: 80px;
            height: 80px;
            background-color: #003CFF;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .moon {
            width: 30px;
            height: 30px;
            background-color: #003CFF;
            border-radius: 50%;
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            animation: orbit 5s linear infinite;
        }
        @keyframes orbit {
            0% { transform: rotate(0deg) translateX(50px) rotate(0deg); }
            100% { transform: rotate(360deg) translateX(50px) rotate(-360deg); }
        }
        .tagline {
            font-size: 1rem !important;
            max-width: 600px;
            margin-bottom: 25px;
            line-height: 1.5;
            padding: 10px;
        }
        .button {
            background: #4c4cff;
            height: 50px;
            width: 150px;
            text-align: center;
            position: relative;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .text {
            font: bold 1rem/1 Poppins, sans-serif;
            color: white;
            transition: opacity 0.3s;
        }
        .progress-bar {
            position: absolute;
            height: 8px;
            width: 0;
            left: 50%;
            border-radius: 200px;
            transform: translateX(-50%);
            background: #3A3D40;
        }
        svg {
            width: 25px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%) translateX(-50%);
            left: 50%;
            opacity: 0;
        }
        .check {
            fill: none;
            stroke: #FFFFFF;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
    </style>
</head>
<body>
    <div class="orbit">
        <div class="planet"></div>
        <div class="moon"></div>
    </div>
    <div class="tagline">
        <p>Welcome to <strong>MoonExe</strong>, a visionary platform revolutionizing automated currency matching and trading, empowering traders to 
        Instantly convert USDT to fiat, anytime, anywhere</strong></p>
        <!--<span class="small">
            Before embarking on your journey, we encourage you to review our Terms and Conditions for a comprehensive understanding of our services.
        </span>-->
    </div>

    <div class="button mx-auto">
        <div class="text">Get Started</div>
        <div class="progress-bar"></div>
        <svg viewBox="0 0 25 30">
            <path class="check" d="M2,19.2C5.9,23.6,9.4,28,9.4,28L23,2"/>
        </svg>
    </div>
    <script>
        var basicTimeline = anime.timeline({
            autoplay: false
        });
        var pathEl = document.querySelector(".check");
        var offset = anime.setDashoffset(pathEl);
        pathEl.setAttribute("stroke-dashoffset", offset);
        basicTimeline
            .add({ targets: ".text", duration: 200, opacity: 0 })
            .add({ targets: ".button", duration: 800, height: 8, width: 200, backgroundColor: "#4c4cff", borderRadius: 100 })
            .add({ targets: ".progress-bar", duration: 1200, width: 200, easing: "linear" })
            .add({ targets: ".button", width: 0, duration: 1 })
            .add({ targets: ".progress-bar", width: 50, height: 50, delay: 300, duration: 500, borderRadius: 50, backgroundColor: "#71DFBE" })
            .add({ targets: pathEl, strokeDashoffset: [offset, 0], duration: 150, easing: "easeInOutSine", complete: function() {
                setTimeout(function() { window.location.href = "{{ route('login') }}"; }, 300);
            }});
        document.querySelector(".button").addEventListener("click", function() { basicTimeline.play(); });
    </script>
</body>
</html>
