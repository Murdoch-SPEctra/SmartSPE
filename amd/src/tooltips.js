define(['core/bootstrap'], function(bs) {
    return {
        init: function() {
            // Find all tooltip elements
            const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipElements.forEach(el => new bs.Tooltip(el));
        }
    };
});
