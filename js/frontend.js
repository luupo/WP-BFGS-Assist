document.addEventListener("DOMContentLoaded", function() {
    const toolbar = document.getElementById("wp-bfsg-toolbar");
    const toggleBtn = document.getElementById("wp-bfsg-toggle");
    if (!toolbar || !toggleBtn) {
        console.error("Required elements not found");
        return;
    }

    // Icon-Typ setzen basierend auf Admin-Einstellung
    toggleBtn.classList.add(
        window.wpBfsgAssist && window.wpBfsgAssist.useFontAwesome 
            ? 'use-fontawesome' 
            : 'use-emoji'
    );

    const MIN_FONT_SIZE = 0.5;  // 50%
    const MAX_FONT_SIZE = 3.0;  // 300%
    const FONT_SIZE_STEP = 0.1; // 10%

    // Initialize scale from localStorage or default
    let currentScale = parseFloat(localStorage.getItem('wp-bfsg-scale')) || 1.0;
    applyScale(currentScale);

    // Remove the old toggle event listener and localStorage for toolbar display
    // Let jQuery handle the toggle functionality

    toolbar.addEventListener("click", function(event) {
        const button = event.target.closest(".wp-bfsg-btn");
        if (!button) return;

        const feature = button.getAttribute("data-feature");
        
        switch (feature) {
            case "keyboard_nav":
            case "disable_animations":
            case "contrast":
            case "readable_font":
            case "mark_titles":
            case "highlight_links": {
                const className = {
                    keyboard_nav: "keyboard-nav-enabled",
                    disable_animations: "disable-animations",
                    contrast: "high-contrast",
                    readable_font: "readable-font",
                    mark_titles: "mark-titles",
                    highlight_links: "highlight-links"
                }[feature];
                
                document.body.classList.toggle(className);
                button.setAttribute('aria-pressed', 
                    document.body.classList.contains(className));
                break;
            }
            case "increase_text":
                if (currentScale < MAX_FONT_SIZE) {
                    currentScale += FONT_SIZE_STEP;
                    applyScale(currentScale);
                }
                break;
            case "decrease_text":
                if (currentScale > MIN_FONT_SIZE) {
                    currentScale -= FONT_SIZE_STEP;
                    applyScale(currentScale);
                }
                break;
            default:
                console.warn("Unknown feature:", feature);
        }
    });

    function applyScale(scale) {
        try {
            // Skaliere alle Text-relevanten Elemente, außer das Toolbar
            const elements = document.querySelectorAll('body, body *:not(.wp-bfsg-toolbar-wrapper):not(.wp-bfsg-toolbar-wrapper *)');
            elements.forEach(element => {
                // Überspringe Elemente, die zum Toolbar gehören
                if (element.closest('.wp-bfsg-toolbar-wrapper')) {
                    return;
                }

                // Hole den original font-size Wert oder setze 'initial'
                const originalSize = element.getAttribute('data-original-size') || 
                    window.getComputedStyle(element).fontSize;
                
                // Speichere original Größe beim ersten Mal
                if (!element.getAttribute('data-original-size')) {
                    element.setAttribute('data-original-size', originalSize);
                }

                // Berechne neue Größe
                const originalPx = parseFloat(originalSize);
                const newSize = originalPx * scale;
                
                // Wende neue Größe an
                element.style.fontSize = `${newSize}px`;
            });

            localStorage.setItem('wp-bfsg-scale', scale.toString());
        } catch (error) {
            console.error("Error updating font sizes:", error);
        }
    }
});

jQuery(document).ready(function($) {
    // Wrap toggle and toolbar in a container
    $('#wp-bfsg-toggle, #wp-bfsg-toolbar').wrapAll('<div class="wp-bfsg-toolbar-wrapper"></div>');
    
    // Ensure toolbar is hidden initially
    $('#wp-bfsg-toolbar').hide();

    // Simplified toggle button click handler
    $('#wp-bfsg-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#wp-bfsg-toolbar').fadeToggle(200);
    });
    
    // Close toolbar when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wp-bfsg-toolbar-wrapper').length) {
            $('#wp-bfsg-toolbar').fadeOut(200);
        }
    });

    // Prevent toolbar clicks from closing the menu
    $('#wp-bfsg-toolbar').on('click', function(e) {
        e.stopPropagation();
    });
});
