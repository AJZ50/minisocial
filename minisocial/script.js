// Dark Mode Toggle with Persistence
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.dark-mode-toggle');
    const isDarkMode = localStorage.getItem('dark-mode') === 'true';

    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        toggle.textContent = 'Switch to Light Mode';
    }

    toggle?.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        toggle.textContent = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        localStorage.setItem('dark-mode', isDark);
    });

    // Slide-in Animation
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.slide-up').forEach(el => {
        observer.observe(el);
    });
});
