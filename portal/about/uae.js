// FMD Page - Static Content

// Each division should have a title and description

const divisions = [
  {
    title: '1. Agri-Fisheries and Food Security.',
    description: 'This thematic area emphasizes developing agriculture and food systems that are economically viable and sustainable to ensure food security as well as to improve the quality of life of farmers and fisherfolks. Research and extension activities on the following commodities shall be given priority: coffee; kaong; makapuno; rice; corn; root crops; high-value crops (banana, pineapple, mango, cacao, and vegetables) poultry and livestock; apiculture; fisheries and aquaculture; urban agriculture; and organic agriculture.'
  },
  {
    title: '2. Biodiversity and Environmental Conservation.',
    description: 'This thematic area concerned with environmental stewardship and equitable allocation and sustainable use of natural resources. Emphases include environmental protection; biodiversity assessment and monitoring: cleaner environment; climate change; inclusive risk reduction management; renewable energy and green technologies; natural resource management; and Ecotourism.'
  },
  {
    title: '3. Smart and Information, Engineering, Communication Technology (ICT), and Industrial Competitiveness.',
    description: 'he term, smart engineering, covers the methods, processes, systems, and tools for the cross-disciplinary, system-oriented development of innovative and interconnected products, services, and infrastructures in the field of engineering. Products and process models shall be developed in which networking encompasses all stages of development, from interdisciplinary design, to production processes and piloting, right the way through product usage and disposal. Smart engineering is about the integration of appropriate product planning, development, and management to ensure the rapid market-ready implementation of innovative products and services and industrial competitiveness through the digital development process.<br><br>The convergence of the latest computer science and ICT developments in the areas of micro-devices, mobile communication, hardware infrastructures, internet and software technologies, image recognition and processing, parallel computing, complex adaptive systems and bioinformatics shall be given emphasis. Strong focus shall also be given to mathematics and statistics applications such as industrial and biological modelling, biomedical, social and theoretical statistics, computational mathematics, discrete pure mathematics and physics/engineering.'
  },
  {
    title: '4. Societal Development and Equity.',
    description: 'This thematic area focuses on educational, criminological, and social sciences researches and development initiatives with emphasis on sustainable development, economic development, gender and development, community development, governance, poverty alleviation, social behavior, attitude and norms, capacity building, peace process and conflict resolution, inclusive disaster risk management and social transitions, pedagogy, special education, ICT and education, among others.'
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
      <img src="uea.jpg" alt="University Extension Agenda" class="uae-image">
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
