/**
 * Admin Navigation System with Smooth Transitions
 * Handles page transitions and navigation state management
 */

class AdminNavigation {
    constructor() {
        this.currentPage = this.getCurrentPageFromURL();
        this.isTransitioning = false;
        this.pages = {
            'dashboard': '/admin/index.html',
            'settings': '/admin/settings.html'
        };
        
        this.init();
    }
    
    init() {
        this.setupNavigation();
        this.setupNavIndicator();
        this.updateActiveNavItem();
        this.animatePageLoad();
    }
    
    getCurrentPageFromURL() {
        const path = window.location.pathname;
        if (path.includes('settings.html')) return 'settings';
        return 'dashboard';
    }
    
    setupNavigation() {
        const navLinks = document.querySelectorAll('.admin-nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                if (this.isTransitioning) return;
                
                const href = link.getAttribute('href');
                const targetPage = href.includes('settings.html') ? 'settings' : 'dashboard';
                
                if (targetPage !== this.currentPage) {
                    this.navigateToPage(targetPage, href);
                }
            });
        });
    }
    
    setupNavIndicator() {
        const navList = document.querySelector('.admin-nav-list');
        let indicator = document.querySelector('.nav-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'nav-indicator';
            navList.appendChild(indicator);
        }
        
        this.updateNavIndicator();
    }
    
    updateNavIndicator() {
        const activeLink = document.querySelector('.admin-nav-link.active');
        const indicator = document.querySelector('.nav-indicator');
        
        if (activeLink && indicator) {
            const navList = document.querySelector('.admin-nav-list');
            const links = Array.from(navList.querySelectorAll('.admin-nav-link'));
            const activeIndex = links.indexOf(activeLink);
            const linkWidth = 100 / links.length;
            
            indicator.style.width = `${linkWidth}%`;
            indicator.style.transform = `translateX(${activeIndex * 100}%)`;
        }
    }
    
    updateActiveNavItem() {
        const navLinks = document.querySelectorAll('.admin-nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            
            const href = link.getAttribute('href');
            const linkPage = href.includes('settings.html') ? 'settings' : 'dashboard';
            
            if (linkPage === this.currentPage) {
                link.classList.add('active');
            }
        });
        
        this.updateNavIndicator();
        this.updatePageIndicator();
    }
    
    updatePageIndicator() {
        const pageIndicator = document.querySelector('.page-indicator');
        if (pageIndicator) {
            pageIndicator.textContent = this.currentPage === 'dashboard' ? 'Dashboard' : 'Settings';
        }
    }
    
    async navigateToPage(targetPage, href) {
        if (this.isTransitioning) return;
        
        this.isTransitioning = true;
        
        // Determine slide direction
        const currentIndex = Object.keys(this.pages).indexOf(this.currentPage);
        const targetIndex = Object.keys(this.pages).indexOf(targetPage);
        const slideDirection = targetIndex > currentIndex ? 'right' : 'left';
        
        // Show loading state
        this.showLoadingState();
        
        try {
            // Load the new page content
            const newContent = await this.loadPageContent(href);
            
            // Perform the transition
            await this.performPageTransition(newContent, slideDirection);
            
            // Update current page
            this.currentPage = targetPage;
            
            // Update URL without page reload
            window.history.pushState({ page: targetPage }, '', href);
            
            // Update navigation
            this.updateActiveNavItem();
            
            // Initialize page-specific functionality
            this.initializePageContent(targetPage);
            
        } catch (error) {
            console.error('Navigation error:', error);
            this.showErrorMessage('Failed to load page. Please try again.');
        } finally {
            this.hideLoadingState();
            this.isTransitioning = false;
        }
    }
    
    async loadPageContent(href) {
        const response = await fetch(href);
        if (!response.ok) {
            throw new Error(`Failed to load page: ${response.status}`);
        }
        
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Extract the main content
        const newMain = doc.querySelector('.admin-main');
        if (!newMain) {
            throw new Error('Invalid page structure');
        }
        
        return newMain.innerHTML;
    }
    
    async performPageTransition(newContent, direction) {
        const currentMain = document.querySelector('.admin-main');
        const currentContent = currentMain.innerHTML;
        
        // Create page containers
        const pageContainer = document.createElement('div');
        pageContainer.className = 'page-container';
        
        const currentPage = document.createElement('div');
        currentPage.className = 'page-content';
        currentPage.innerHTML = currentContent;
        
        const newPage = document.createElement('div');
        newPage.className = `page-content slide-in-${direction}`;
        newPage.innerHTML = newContent;
        
        pageContainer.appendChild(currentPage);
        pageContainer.appendChild(newPage);
        
        // Replace main content
        currentMain.innerHTML = '';
        currentMain.appendChild(pageContainer);
        
        // Force reflow
        newPage.offsetHeight;
        
        // Start transition
        return new Promise((resolve) => {
            setTimeout(() => {
                currentPage.classList.add(`slide-out-${direction === 'right' ? 'left' : 'right'}`);
                newPage.classList.remove(`slide-in-${direction}`);
                newPage.classList.add('slide-in-active');
                
                setTimeout(() => {
                    // Replace with final content
                    currentMain.innerHTML = newContent;
                    this.animatePageLoad();
                    resolve();
                }, 400);
            }, 50);
        });
    }
    
    animatePageLoad() {
        const cards = document.querySelectorAll('.admin-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    showLoadingState() {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
        
        document.querySelector('.admin-main').appendChild(loadingOverlay);
        
        setTimeout(() => {
            loadingOverlay.classList.add('active');
        }, 10);
    }
    
    hideLoadingState() {
        const loadingOverlay = document.querySelector('.loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
            setTimeout(() => {
                loadingOverlay.remove();
            }, 300);
        }
    }
    
    showErrorMessage(message) {
        // Create error popup
        const errorPopup = document.createElement('div');
        errorPopup.className = 'popup-message error show';
        errorPopup.textContent = message;
        
        document.body.appendChild(errorPopup);
        
        setTimeout(() => {
            errorPopup.classList.remove('show');
            setTimeout(() => {
                errorPopup.remove();
            }, 300);
        }, 3000);
    }
    
    initializePageContent(page) {
        // Initialize page-specific functionality
        if (page === 'dashboard') {
            this.initializeDashboard();
        } else if (page === 'settings') {
            this.initializeSettings();
        }
    }
    
    initializeDashboard() {
        // Re-initialize dashboard functionality
        if (window.initializeDashboard) {
            window.initializeDashboard();
        }
    }
    
    initializeSettings() {
        // Re-initialize settings functionality
        if (window.initializeSettings) {
            window.initializeSettings();
        }
    }
}

// Initialize navigation when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminNavigation = new AdminNavigation();
});

// Handle browser back/forward buttons
window.addEventListener('popstate', (event) => {
    if (event.state && event.state.page) {
        const targetPage = event.state.page;
        const href = window.adminNavigation.pages[targetPage];
        
        if (href && targetPage !== window.adminNavigation.currentPage) {
            window.adminNavigation.navigateToPage(targetPage, href);
        }
    }
});