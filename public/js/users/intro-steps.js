document.addEventListener("DOMContentLoaded", function () {
    // STEP: Assign DOM IDs BEFORE creating tour steps
    document.querySelector("h2.text-custom")?.setAttribute("id", "balanceHighlight");
    document.querySelector("#depositButton")?.closest("div.col-3, div.col-md-auto")?.setAttribute("id", "depositStep");

    const steps = [
        { id: "balanceHighlight",         title: "1. Your Account Balance",      content: "This is your current account balance in USDT." },
        { id: "depositStep",              title: "2. Deposit Funds",              content: "Click here to deposit USDT into your wallet." },
        { id: "activateTradingAccount",   title: "3. Activate Trading Account",   content: "Click to activate your trading margin account." },
        { id: "tradeButton",              title: "4. Start Trading",              content: "Begin your trading journey by clicking here!" },
        { id: "faqLinkTour",              title: "5. Need Help?",                 content: "More FAQs are available here. Tap to explore." },
        { id: "restartTourBtn",           title: "6. Reopen Tutorial",            content: "Click here anytime to view this tutorial again." }
    ];

    // Factory function to create a new Tour instance
    function createTour() {
        const newTour = new Tour(steps, {
            translations: { next: "Next", previous: "Back", finish: "Finish" }
        });

        // Override updateActiveElement for scroll-into-view
        newTour.updateActiveElement = function () {
            for (let i = 0; i < this.steps.length; i++) {
                const e = this.getContainerByIndex(i);
                if (i === this.currentTab) {
                    e.classList.add("tour-active-element");
                    e.scrollIntoView({ behavior: "smooth", block: "center" });
                } else {
                    e.classList.remove("tour-active-element");
                }
            }
        };

        return newTour;
    }

    // Init tour
    let tour = createTour();

    // Scroll into view on each step
    document.body.addEventListener("shown.bs.popover", function (e) {
        if (!e.target.id || !steps.some(step => step.id === e.target.id)) return;

        setTimeout(() => {
            const el = document.getElementById(e.target.id);
            if (el) el.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 10);
    });

    // Show on first visit
    if (!localStorage.getItem("hasSeenTour")) {
        tour.show();
        localStorage.setItem("hasSeenTour", "1");
    }

    // Restart handler
    document.getElementById("restartTourBtn")?.addEventListener("click", function () {
        localStorage.removeItem("hasSeenTour");

        // Cleanup popovers manually to avoid stacking
        document.querySelectorAll('.popover').forEach(el => el.remove());

        // Recreate fresh tour and start
        tour = createTour();
        tour.show();
    });
});
