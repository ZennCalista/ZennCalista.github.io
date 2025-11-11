// Submission Form Page - Static Content 

// ============================================
// EDIT YOUR CONTENT HERE
// ============================================
// Add your sections and descriptions below
// Each section should have a title and description

const sections = [
  {
    title: 'Article 4.',
    description: 'Submission of Proposal, Implementation, and Monitoring and Evaluation of Extension Programs, Projects, and Activities'
  },
  {
    title: 'Section 1.',
    description: 'Extension Interventions, Programs/Projects, and Activities. The University shall engage in the following extension interventions, programs/projects, and activities: (1) Capability Building Programs; (2) Technical Assistance, Advisory Services, Consultancy, and Other Support Services; (3) Communication and Information Services; (4) Technology Promotion, Transfer, Utilization, and Commercialization; (5) Community-Based Development Projects, Social Innovations, and Other Programs/Projects; and (6) Community Outreach Activities and High-Impact and Integrated Extension Volunteer Programs.'
  },
  {
    title: '1.1.1. Program/Project',
    description: 'College/campus/unit program/project proposal must be prepared using the prescribed format of the Extension Services and must be endorsed by the College Dean/Campus Administrator/Unit Director. An extension project is a unique endeavor composed of various arches strategies, methods, and interrelations which must be completed to attain outcome over a specified period. The scansion program is composed of at least four related extension projects.'
  },
  {
    title: '1.1.2. Activity',
    description: 'Other extension activities that are not part of any program or project must be approved prior to implementation. An activity proposal must be prepared using the prescribed format of the Extension Services and must be endorsed by the College Dean/Campus Administrator/Unit Director to the Extension Services. Activity proposals included in the approved extension plan of the college/campus/unit shall Extension activities shall include conduct of be prioritized. training, provision of technical assistance, advisory services, consultancy, and other support services, provision of communication and information services, and conduct of community outreach and volunteer activities.'
  },
  {
    title: '1.1.3. Eligibility of the Proponent',
    description: 'Any faculty or extension staff (temporary or permanent) is encouraged to submit proposals. The eligible program/project/activity extension workers must possess the academic qualifications and track record of successful implementation programs/projects/activities.'
  },
  {
    title: '1.1.4. Screening and Approval Program/project',
    description: 'Proposals submitted by the college are recorded and presented by the Extension Services. After passing the prescreening, it shall be presented by the proponent to the selected members of the UREC for evaluation. Once the proposal passes the evaluation, it shall be endorsed to the OVPRE for the subsequent approval of the University President. It shall also be forwarded to the Administrative Council for subsequent endorsement to the Board of Regents for confirmation. <br><br> Activity proposals submitted by the college/campus/unit shall be recorded and evaluated by the Extension Services. After passing the evaluation, it shall be forwarded to the OVPRE for subsequent endorsement to the University President for approval.'
  },
  {
    title: '1.2.1. Eligibility of the Proponent',
    description: 'Any faculty or extension staff (temporary or permanent) is eligible and encouraged to submit proposals. The extension implementer must possess the academic qualifications and track record of successful implementation of projects.<br><br>The Extension Services and/or OVPRE may directly solicit proposals from selected faculty and staff as the need arises.'
  },
  {
    title: '1.2.2. Submission of Proposal',
    description: 'All colleges/ encouraged to program/project proposals using the prescribed format of the Extension Services and it must be endorsed by the College Dean/Campus Administrator/Unit Director.'
  },
  {
    title: '1.3.1. Eligibility of the Proponent.',
    description: 'Any faculty or extension staff (temporary or permanent) is eligible and encouraged to submit proposals. The extension worker must possess the academic qualifications and track record of successful implementation of projects.<br><br>The Extension Services and/or OVPRE may directly solicit proposals from selected faculty and staff as the need arises.'
  },
  {
    title: '1.3.2. Submission and Prescreening of Proposal',
    description: 'Prior to submission of the proposal to the funding agency, it must be endorsed by the College Dean/Campus Administrator/Unit Director to the Extension Services. Once recorded and prescreened, the Extension Services shall assist in requesting the endorsement of the proposal from the Office of the Vice President for Research and Extension and the Office of the President to the agency. The proposal shall also be forwarded to the REC and Administrative Council for subsequent endorsement to the Board of Regents for confirmation. Section 2. Evaluation of the Extension Program/Project Proposal 2.1 Submission of Program/Project Proposal. The Extension Services requires that proposals be submitted in electronic copy together with: (1) endorsement letter from the College Dean/Campus Administrator/Unit Head; (2) accomplished HGDG checklist; and (3) curriculum vitae of the proponent. Copies of the proposal including the necessary attachments must be sent via email at extension@cvsu.edu.ph. Endorsement letter should be addressed to: The Director Extension Services Cavite State University Don Severino delas Alas Campus Indang, Cavite <br><br>Note: Prior to submission, all proposals must have been pre-evaluated by the proponent using the Harmonized Gender and Development Guidelines (HGDG) Program/Project Development Evaluation Checklist.'
  },
  {
    title: '2.2. Pre-Screening of Program/Project Proposal',
    description: 'All proposals submitted shall be prescreened by the MED of the Extension Services to determine its completeness, conformity with the format, non-duplication, and alignment with the University Extension Agenda. A representative from the concerned Commodity RDE Center shall also be requested to initially screen commodity-specific extension proposals (i.e., coffee, makapuno, and kaong) received from the colleges/campuses. In case there is a need for revision, one week shall be given to the proponent to revise the proposal.'
  }
];


// Initialize content
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
}

// Display static content
function displayContent() {
  const container = document.getElementById('content-container');
  if (!container) return;
  
  // Generate HTML from sections array with scroll animation support
  const contentHTML = sections.map((section, index) => `
    <div class="section-item" data-index="${index}">
      <h4>${section.title}</h4>
      <p>${section.description}</p>
    </div>
  `).join('\n');
  
  container.innerHTML = `
    <div class="content-card">
      <div class="content-text">
        ${contentHTML}
      </div>
    </div>
  `;
  
  // Initialize scroll animations after content is rendered
  initScrollAnimations();
}

// Initialize scroll animations
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
  
  document.querySelectorAll('.section-item').forEach(item => {
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
      const name = `${data.user.firstname || ''} ${data.user.lastname || ''}`.trim() || data.user.email;
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
