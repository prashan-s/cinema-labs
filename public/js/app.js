// Shows Management Application
class ShowsManager {
    constructor() {
        this.shows = this.loadShows();
        this.currentSlide = 0;
        this.init();
    }

    init() {
        // Initialize the application based on current page
        const path = window.location.pathname;
        
        if (path === '/') {
            this.initCarousel();
        } else if (path === '/shows') {
            this.renderShows();
        } else if (path === '/add-show') {
            this.initAddShowForm();
        } else if (path === '/trending' || path === '/movies' || path === '/tv-shows') {
            this.initAddToCollectionButtons();
        }

        // Add navigation event listeners
        this.initNavigation();
    }

    initCarousel() {
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');
        const indicators = document.querySelectorAll('.indicator');
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => this.prevSlide());
            nextBtn.addEventListener('click', () => this.nextSlide());
        }

        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => this.goToSlide(index));
        });

        // Auto-advance carousel
        setInterval(() => this.nextSlide(), 5000);
    }

    prevSlide() {
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.indicator');
        
        slides[this.currentSlide].classList.remove('active');
        indicators[this.currentSlide].classList.remove('active');
        
        this.currentSlide = (this.currentSlide - 1 + slides.length) % slides.length;
        
        slides[this.currentSlide].classList.add('active');
        indicators[this.currentSlide].classList.add('active');
    }

    nextSlide() {
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.indicator');
        
        if (slides.length === 0) return;
        
        slides[this.currentSlide].classList.remove('active');
        indicators[this.currentSlide].classList.remove('active');
        
        this.currentSlide = (this.currentSlide + 1) % slides.length;
        
        slides[this.currentSlide].classList.add('active');
        indicators[this.currentSlide].classList.add('active');
    }

    goToSlide(index) {
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.indicator');
        
        slides[this.currentSlide].classList.remove('active');
        indicators[this.currentSlide].classList.remove('active');
        
        this.currentSlide = index;
        
        slides[this.currentSlide].classList.add('active');
        indicators[this.currentSlide].classList.add('active');
    }

    initAddToCollectionButtons() {
        const buttons = document.querySelectorAll('.add-to-collection');
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                const title = e.target.getAttribute('data-title');
                const type = e.target.getAttribute('data-type');
                this.addToCollection(title, type, e.target);
            });
        });
    }

    addToCollection(title, type, button) {
        const show = {
            id: Date.now(),
            title: title,
            genre: type === 'movie' ? 'movie' : 'tv-show',
            year: new Date().getFullYear(),
            description: `Added from trending ${type}s`,
            dateAdded: new Date().toISOString()
        };

        this.addShow(show);
        this.showNotification(`"${title}" added to your collection!`, 'success');
        
        // Update button state
        button.textContent = 'Added!';
        button.classList.remove('btn-primary');
        button.classList.add('btn-success');
        button.disabled = true;
        
        // Reset button after 3 seconds
        setTimeout(() => {
            button.textContent = 'Add to Collection';
            button.classList.remove('btn-success');
            button.classList.add('btn-primary');
            button.disabled = false;
        }, 3000);
    }

    initNavigation() {
        // Highlight current page in navigation
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.style.color = '#3498db';
            }
        });
    }

    initAddShowForm() {
        const form = document.getElementById('add-show-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleAddShow(e));
        }
    }

    handleAddShow(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const show = {
            id: Date.now(),
            title: formData.get('title'),
            genre: formData.get('genre'),
            year: parseInt(formData.get('year')),
            description: formData.get('description'),
            dateAdded: new Date().toISOString()
        };

        this.addShow(show);
        this.showNotification('Show added successfully!', 'success');
        event.target.reset();
        
        // Redirect to shows page after a short delay
        setTimeout(() => {
            window.location.href = '/shows';
        }, 1500);
    }

    addShow(show) {
        this.shows.push(show);
        this.saveShows();
    }

    renderShows() {
        const container = document.getElementById('shows-container');
        if (!container) return;

        if (this.shows.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No shows added yet.</p>
                    <a href="/add-show" class="btn btn-primary">Add Your First Show</a>
                </div>
            `;
            return;
        }

        container.innerHTML = this.shows.map(show => `
            <div class="show-card" data-id="${show.id}">
                <h3>${this.escapeHtml(show.title)}</h3>
                <span class="genre">${this.escapeHtml(show.genre)}</span>
                <div class="year">${show.year}</div>
                ${show.description ? `<div class="description">${this.escapeHtml(show.description)}</div>` : ''}
                <div class="show-actions">
                    <button class="btn btn-secondary btn-sm" onclick="showsManager.editShow(${show.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="showsManager.deleteShow(${show.id})">Delete</button>
                </div>
            </div>
        `).join('');
    }

    editShow(id) {
        const show = this.shows.find(s => s.id === id);
        if (show) {
            // For now, just show an alert. In a full implementation, 
            // you would create an edit form or modal
            alert(`Editing: ${show.title}\n\nThis feature will be implemented in a future update.`);
        }
    }

    deleteShow(id) {
        if (confirm('Are you sure you want to delete this show?')) {
            this.shows = this.shows.filter(s => s.id !== id);
            this.saveShows();
            this.renderShows();
            this.showNotification('Show deleted successfully!', 'success');
        }
    }

    loadShows() {
        try {
            const stored = localStorage.getItem('shows');
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading shows:', error);
            return [];
        }
    }

    saveShows() {
        try {
            localStorage.setItem('shows', JSON.stringify(this.shows));
        } catch (error) {
            console.error('Error saving shows:', error);
            this.showNotification('Error saving data', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '1rem 1.5rem',
            borderRadius: '5px',
            color: 'white',
            fontWeight: 'bold',
            zIndex: '1000',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });

        // Set background color based on type
        switch (type) {
            case 'success':
                notification.style.backgroundColor = '#27ae60';
                break;
            case 'error':
                notification.style.backgroundColor = '#e74c3c';
                break;
            default:
                notification.style.backgroundColor = '#3498db';
        }

        // Add to page
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Remove after delay
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.showsManager = new ShowsManager();
});

// Add some demo data if none exists
document.addEventListener('DOMContentLoaded', () => {
    const stored = localStorage.getItem('shows');
    if (!stored) {
        const demoShows = [
            {
                id: 1,
                title: "Breaking Bad",
                genre: "drama",
                year: 2008,
                description: "A high school chemistry teacher turned methamphetamine producer.",
                dateAdded: new Date().toISOString()
            },
            {
                id: 2,
                title: "The Office",
                genre: "comedy",
                year: 2005,
                description: "A mockumentary about office employees at a paper company.",
                dateAdded: new Date().toISOString()
            }
        ];
        localStorage.setItem('shows', JSON.stringify(demoShows));
    }
});