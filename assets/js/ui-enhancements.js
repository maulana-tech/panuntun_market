// Enhanced UI Interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add animation classes to cards
    const cards = document.querySelectorAll('.modern-card');
    cards.forEach(card => {
        card.style.animation = 'slideIn 0.5s ease-out';
    });

    // Enhanced buttons interaction
    const buttons = document.querySelectorAll('.enhanced-button');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.animation = 'pulse 0.5s ease-in-out';
        });
        button.addEventListener('animationend', function() {
            this.style.animation = '';
        });
    });

    // Table row hover effects
    const tableRows = document.querySelectorAll('.modern-table tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'transform 0.2s ease';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// Loading state handler
function showLoading(button) {
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<div class="loading-spinner mx-auto"></div>';
    return originalContent;
}

function hideLoading(button, originalContent) {
    button.disabled = false;
    button.innerHTML = originalContent;
}