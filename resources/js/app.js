// Theme toggle. The initial class is applied by an inline script in the layout head
// (before first paint, so there is no flash); this file only handles clicks and re-syncs
// the buttons after Livewire swaps the DOM.

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-option]');

    if (! button) {
        return;
    }

    localStorage.setItem('theme', button.dataset.themeOption);
    window.applyTheme();
});

document.addEventListener('DOMContentLoaded', () => window.applyTheme());
document.addEventListener('livewire:navigated', () => window.applyTheme());
