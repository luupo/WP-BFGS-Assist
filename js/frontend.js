document.addEventListener("DOMContentLoaded", function() {
    const toolbar = document.getElementById("wp-bfsg-toolbar");
    const toggleBtn = document.getElementById("wp-bfsg-toggle");
    if (!toolbar || !toggleBtn) return;

    let currentFontSize = 100;

    // Toggle-Funktion für die Toolbar
    toggleBtn.addEventListener("click", function() {
        toolbar.style.display = (toolbar.style.display === "block") ? "none" : "block";
    });

    toolbar.addEventListener("click", function(event) {
        const button = event.target.closest(".wp-bfsg-btn");
        if (!button) return;

        const feature = button.getAttribute("data-feature");
        
        switch (feature) {
            case "keyboard_nav":
                document.body.classList.toggle("keyboard-nav-enabled");
                break;
            case "disable_animations":
                document.body.classList.toggle("disable-animations");
                break;
            case "contrast":
                document.body.classList.toggle("high-contrast");
                break;
            case "increase_text":
                currentFontSize += 10;
                document.body.style.fontSize = currentFontSize + "%";
                break;
            case "decrease_text":
                if (currentFontSize > 90) {
                    currentFontSize -= 10;
                    document.body.style.fontSize = currentFontSize + "%";
                }
                break;
            case "readable_font":
                document.body.classList.toggle("readable-font");
                break;
            case "mark_titles":
                document.body.classList.toggle("mark-titles");
                break;
            case "highlight_links":
                document.body.classList.toggle("highlight-links");
                break;
            default:
                console.warn("Unbekannte Funktion: ", feature);
        }
    });
});
