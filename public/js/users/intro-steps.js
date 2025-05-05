document.addEventListener("DOMContentLoaded", function () {
    const steps = [
        { id: "balanceHighlight",         title: "1. Your Account Balance",      content: "This is your current account balance in USDT." },
        { id: "depositStep",              title: "2. Deposit Funds",              content: "Click here to deposit USDT into your wallet." },
        { id: "activateTradingAccount",   title: "3. Activate Trading Account",    content: "Click to activate your trading margin account." },
        { id: "tradeButton",              title: "4. Start Trading",              content: "Begin your trading journey by clicking here!" }
    ];

    // Initialize IDs for steps
    document.querySelector("h2.text-custom")?.setAttribute("id", "balanceHighlight");
    document.querySelector("#depositButton")?.closest("div.col-3, div.col-md-auto")?.setAttribute("id", "depositStep");

    const tour = new Tour(steps, {
        translations: { next: "Next", previous: "Back", finish: "Finish" }
    });

    // Listen for popover shown and then nudge the scroll
    document.body.addEventListener("shown.bs.popover", function (e) {
        if (!e.target.id || !["balanceHighlight","depositStep","activateTradingAccount","tradeButton"].includes(e.target.id)) {
            return;
        }
        setTimeout(() => {
            const el = document.getElementById(e.target.id);
            if (!el) return;
            el.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 10);
    });
    
    // Also update updateActiveElement:
    Tour.prototype.updateActiveElement = function() {
        for (var i = 0; i < this.steps.length; i++) {
            var e = this.getContainerByIndex(i);
            if (i === this.currentTab) {
                e.classList.add("tour-active-element");
                e.scrollIntoView({ behavior: "smooth", block: "center" });
            } else {
                e.classList.remove("tour-active-element");
            }
        }
    }


    if (!localStorage.getItem("hasSeenTour")) {
        tour.show();
        localStorage.setItem("hasSeenTour", "1");
    }
    
   document.getElementById("restartTourBtn")?.addEventListener("click", function () {
        localStorage.removeItem("hasSeenTour");
        tour.show();
    });
});
