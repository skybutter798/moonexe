<div id="load_screen">
    <div class="loader-content">
        <div class="orbit">
            <div class="planet"></div>
            <div class="moon"></div>
        </div>
    </div>
</div>

<style>
    /* Full-screen loader overlay */
    #load_screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: white;
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }
    /* Fade-out effect for the loader */
    #load_screen.fade-out {
        opacity: 0;
        visibility: hidden;
    }
    /* Loader content container */
    .loader-content {
        text-align: center;
    }
    /* Orbit container */
    .orbit {
        position: relative;
        width: 150px;
        height: 150px;
    }
    /* Static planet styling */
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
    /* Moon with orbit animation */
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
    /* Keyframes for orbit animation */
    @keyframes orbit {
        0% {
            transform: rotate(0deg) translateX(50px) rotate(0deg);
        }
        100% {
            transform: rotate(360deg) translateX(50px) rotate(-360deg);
        }
    }
</style>

<script>
    // Define a function to hide the loader after a minimum delay
    function hideLoader() {
        var loader = document.getElementById("load_screen");
        if (loader) {
            // Calculate elapsed time since our recorded start time
            var startTime = window.loaderStartTime || performance.now();
            var elapsedTime = performance.now() - startTime;
            var minimumDuration = 2000; // 2000ms = 2 seconds
            var remainingTime = Math.max(0, minimumDuration - elapsedTime);

            setTimeout(function() {
                loader.classList.add("fade-out");
                // Remove the loader element after the fade-out transition (600ms here)
                setTimeout(function() {
                    loader.style.display = "none";
                }, 600);
            }, remainingTime);
        }
    }

    // If the document is already loaded, call hideLoader immediately.
    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener("load", hideLoader);
    }
</script>