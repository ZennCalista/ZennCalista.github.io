// FMD Page - Static Content

// Each division should have a title and description

const divisions = [
  {
    title: 'a. Community Engagement and External Relations Division (CEERD)',
    description: 'The Community Engagement and External Relations Division facilitates the assessment, conduct of community needs, development of extension programs, projects and other community outreach activities, establishment of partnerships and linkages with various stakeholders.'
  },
  {
    title: 'b. Technology Promotion Division (TPD)',
    description: 'The Promotion Division validates, technology packages, and disseminates appropriate and matured technologies.'
  },
  {
    title: 'c. Training and Courseware Development Division (TCDD)',
    description: 'The Training and Courseware Development Division plans and conducts training programs based on the needs of the target communities/clienteles.'
  },
  {
    title: 'd. Technology Demonstration Farm Division (TDFD)',
    description: 'The Technology Demonstration Farm Division oversees the operations of the techno- demo farm and showcases agri-fisheries matured technologies.'
  },
  {
    title: 'e. Monitoring and Evaluation Division (MED).',
    description: 'The Monitoring and Evaluation Division takes charge of the overall monitoring and evaluation of on-going and completed extension programs, projects, and activities of the colleges, campuses, and other extension implementing units of the University.'
  }
];

// Initialize
document.addEventListener('DOMContentLoaded', async function() {
  loadSession();
  setupSidebar();
  loadCarouselImage();
  displayContent();
});

// Load single carousel image
async function loadCarouselImage() {
  try {
    const response = await fetch(`../home/backend/get_dept_carousel_images.php?_=${Date.now()}`);
    const data = await response.json();
    
    if (data.success && data.images && data.images.length > 0) {
      const backgroundElement = document.getElementById('carousel-background');
      if (backgroundElement) {
        backgroundElement.style.backgroundImage = `url('${data.images[0].src}')`;
      }
    }
  } catch (error) {
    console.error('Error loading carousel image:', error);
  }
  
  // Hide carousel arrows since there's only one static image
  const prevBtn = document.getElementById('category-carousel-prev');
  const nextBtn = document.getElementById('category-carousel-next');
  if (prevBtn) prevBtn.style.display = 'none';
  if (nextBtn) nextBtn.style.display = 'none';
}

// Display static content
function displayContent() {
  const container = document.getElementById('content-container');
  if (!container) return;
  
  // Generate HTML from divisions array with animation classes
  const contentHTML = divisions.map((division, index) => `
    <div class="division-item" data-index="${index}">
      <h4>${division.title}</h4>
      <p>${division.description}</p>
    </div>
  `).join('\n');
  
  container.innerHTML = `
    <div class="content-card">
      <div class="content-text">
        ${contentHTML}
      </div>
    </div>
  `;
  
  // Initialize scroll animations
  initScrollAnimations();
}

// Initialize scroll animations for divisions
function initScrollAnimations() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  });
  
  // Observe all division items
  document.querySelectorAll('.division-item').forEach(item => {
    observer.observe(item);
  });
}

// Load session info
async function loadSession() {
  try {
    const res = await fetch('../home/backend/session_user.php', { credentials: 'include' });
    if (!res.ok) throw new Error('Not authenticated');
    const data = await res.json();
    if (data && data.authenticated && data.user) {
      const name = `(${data.user.firstname || ''} ${data.user.lastname || ''}).trim() || data.user.email`;
      document.getElementById('user-name').textContent = name;
      document.getElementById('user-role').textContent = data.user.role || 'Online';
      window.userRole = data.user.role;
      
      const authLink = document.getElementById('auth-link');
      const authText = document.getElementById('auth-link-text');
      authLink.href = '#';
      authText.textContent = 'Logout';
      
      authLink.onclick = function(e) {
        e.preventDefault();
        fetch('../../register/logout.php')
          .then(() => window.location.reload())
          .catch(err => {
            console.error('Logout error:', err);
            window.location.reload();
          });
      };
      
      document.querySelectorAll('.auth-required').forEach(el => {
        el.style.display = '';
      });
      
      if (data.user.role === 'admin') {
        document.querySelectorAll('.admin-only').forEach(el => {
          el.style.display = '';
        });
      }
    } else {
      resetToGuest();
    }
  } catch (e) {
    resetToGuest();
  }
}

function resetToGuest() {
  document.getElementById('user-name').textContent = 'Guest';
  document.getElementById('user-role').textContent = 'Not signed in';
  window.userRole = null;
  const authLink = document.getElementById('auth-link');
  const authText = document.getElementById('auth-link-text');
  authLink.href = '../../register/index.html';
  authText.textContent = 'Login';
  authLink.onclick = null;
  
  document.querySelectorAll('.auth-required').forEach(el => {
    el.style.display = 'none';
  });
}

// Sidebar menu functionality
function setupSidebar() {
  const menuIcon = document.querySelector('.menu-icon');
  const sidebar = document.getElementById('app-sidebar');
  const overlay = document.getElementById('app-sidebar-overlay');
  const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
  
  function toggleSidebar() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    menuIcon.classList.toggle('active');
  }
  
  menuIcon.addEventListener('click', toggleSidebar);
  overlay.addEventListener('click', toggleSidebar);
  
  if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener('click', toggleSidebar);
  }
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
      toggleSidebar();
    }
  });
}
