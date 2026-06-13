
document.addEventListener('DOMContentLoaded', () => {
    console.log('SMS Pro Dashboard Initialized');

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.style.transition = 'all 0.3s ease';
        badge.addEventListener('mouseover', () => {
            badge.style.transform = 'scale(1.05)';
        });
        badge.addEventListener('mouseout', () => {
            badge.style.transform = 'scale(1)';
        });
    });

    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.opacity = '0';
            setTimeout(() => errorMessage.style.display = 'none', 500);
        }, 5000);
    }
});
