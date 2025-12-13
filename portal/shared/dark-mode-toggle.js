/**
 * Dark Mode Toggle - Floating Action Button
 * Provides theme switching functionality with localStorage persistence
 */

class DarkModeToggle {
  constructor() {
    this.toggleBtn = null;
    this.iconElement = null;
    this.currentTheme = 'light';
    this.storageKey = 'portal-theme-preference';

    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    // Get DOM elements
    this.toggleBtn = document.getElementById('dark-mode-toggle');
    this.iconElement = document.getElementById('fab-icon');

    if (!this.toggleBtn || !this.iconElement) {
      console.warn('Dark mode toggle elements not found');
      return;
    }

    // Load saved preference or detect system preference
    this.loadSavedPreference();

    // Setup event listeners
    this.setupEventListeners();

    // Apply initial theme
    this.applyTheme();

    console.log('Dark mode toggle initialized');
  }

  loadSavedPreference() {
    try {
      const saved = localStorage.getItem(this.storageKey);
      if (saved === 'dark' || saved === 'light') {
        this.currentTheme = saved;
      } else {
        // Check system preference if no saved preference
        this.detectSystemPreference();
      }
    } catch (error) {
      console.warn('Unable to access localStorage:', error);
      this.detectSystemPreference();
    }
  }

  detectSystemPreference() {
    // Check if user prefers dark mode
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      this.currentTheme = 'dark';
    } else {
      this.currentTheme = 'light';
    }
  }

  setupEventListeners() {
    // Toggle button click
    this.toggleBtn.addEventListener('click', () => this.toggleTheme());

    // Keyboard support
    this.toggleBtn.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.toggleTheme();
      }
    });

    // Listen for system theme changes
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        // Only auto-switch if no manual preference is saved
        if (!localStorage.getItem(this.storageKey)) {
          this.currentTheme = e.matches ? 'dark' : 'light';
          this.applyTheme();
        }
      });
    }
  }

  toggleTheme() {
    // Switch theme
    this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';

    // Apply the change
    this.applyTheme();

    // Save preference
    this.savePreference();

    // Provide feedback (optional haptic feedback for mobile)
    if ('vibrate' in navigator) {
      navigator.vibrate(50);
    }
  }

  applyTheme() {
    const html = document.documentElement;

    if (this.currentTheme === 'dark') {
      html.setAttribute('data-theme', 'dark');
      this.iconElement.textContent = '☀️'; // Sun icon for switching to light
      this.toggleBtn.setAttribute('aria-label', 'Switch to light mode');
      this.toggleBtn.setAttribute('title', 'Switch to light mode');
    } else {
      html.removeAttribute('data-theme');
      this.iconElement.textContent = '🌙'; // Moon icon for switching to dark
      this.toggleBtn.setAttribute('aria-label', 'Switch to dark mode');
      this.toggleBtn.setAttribute('title', 'Switch to dark mode');
    }

    // Dispatch custom event for other scripts to listen to
    const themeChangeEvent = new CustomEvent('themeChange', {
      detail: { theme: this.currentTheme }
    });
    document.dispatchEvent(themeChangeEvent);
  }

  savePreference() {
    try {
      localStorage.setItem(this.storageKey, this.currentTheme);
    } catch (error) {
      console.warn('Unable to save theme preference:', error);
    }
  }

  // Public method to get current theme
  getCurrentTheme() {
    return this.currentTheme;
  }

  // Public method to set theme programmatically
  setTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
      this.currentTheme = theme;
      this.applyTheme();
      this.savePreference();
    }
  }

  // Public method to reset to system preference
  resetToSystemPreference() {
    localStorage.removeItem(this.storageKey);
    this.detectSystemPreference();
    this.applyTheme();
  }
}

// Initialize when DOM is ready
let darkModeToggle;
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    darkModeToggle = new DarkModeToggle();
  });
} else {
  darkModeToggle = new DarkModeToggle();
}

// Export for potential use by other scripts
window.DarkModeToggle = DarkModeToggle;
window.darkModeToggle = darkModeToggle;