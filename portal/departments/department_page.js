// Department data
const departments = [
  {
    id: 1,
    name: 'Department of Biological and Physical Sciences',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
    logo: '../images/LOGOS/dp1.png',
    backgroundImage: '../images/download.jpg'
  },
  {
    id: 2,
    name: 'Department of Computer Studies',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp2.png',
    backgroundImage: '../images/download1.jpg'
  },
  {
    id: 3,
    name: 'Department of Hospitality Management',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp3.png',
    backgroundImage: '../images/download2.jpg'
  },
  {
    id: 4,
    name: 'Department of Languages and Mass Communication',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp4.png',
    backgroundImage: '../images/download.jpg'
  },
  {
    id: 5,
    name: 'Department of Management',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp5.png',
    backgroundImage: '../images/download1.jpg'
  },
  {
    id: 6,
    name: 'Department of Physical Education',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp6.png',
    backgroundImage: '../images/download2.jpg'
  },
  {
    id: 7,
    name: 'Department of Social Sciences and Humanities',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp7.png',
    backgroundImage: '../images/download.jpg'
  },
  {
    id: 8,
    name: 'Teacher Education Department',
    description: 'Department Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod',
    logo: '../images/LOGOS/dp8.png',
    backgroundImage: '../images/download1.jpg'
  }
];

let allPrograms = [];
let currentDepartmentId = null;
let currentDeptIndex = 0;

// Initialize
document.addEventListener('DOMContentLoaded', async function() {
  loadSession();
  setupSidebar();
  setupCarousel();
  
  // Load programs first, THEN check URL params and display
  await loadAllPrograms();
  checkURLParams();
});

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
  
  // Add click handler for sidebar close button
  if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener('click', toggleSidebar);
  }
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
      toggleSidebar();
    }
  });
}

// Department carousel navigation
function setupCarousel() {
  const prevBtn = document.getElementById('dept-carousel-prev');
  const nextBtn = document.getElementById('dept-carousel-next');
  
  prevBtn.addEventListener('click', () => {
    currentDeptIndex = (currentDeptIndex - 1 + departments.length) % departments.length;
    const deptId = departments[currentDeptIndex].id;
    loadDepartment(deptId);
  });
  
  nextBtn.addEventListener('click', () => {
    currentDeptIndex = (currentDeptIndex + 1) % departments.length;
    const deptId = departments[currentDeptIndex].id;
    loadDepartment(deptId);
  });
  
  // Sidebar card clicks
  document.querySelectorAll('.sidebar-card').forEach(card => {
    card.addEventListener('click', function() {
      const deptId = parseInt(this.getAttribute('data-dept'));
      loadDepartment(deptId);
    });
  });
}

// Check URL parameters
function checkURLParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const deptId = urlParams.get('dept');
  
  if (deptId) {
    loadDepartment(parseInt(deptId));
  } else {
    // Load department 1 by default
    loadDepartment(1);
  }
}

// Load all programs
async function loadAllPrograms() {
  try {
    const response = await fetch('../home/backend/get_programs.php');
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const programs = await response.json();
    
    if (programs.error) {
      console.error('Error loading programs:', programs.error);
      return;
    }
    
    allPrograms = Array.isArray(programs) ? programs : [];
    console.log('Programs loaded:', allPrograms.length, 'programs');
  } catch (error) {
    console.error('Error loading programs:', error);
    allPrograms = [];
  }
}

// Load specific department
function loadDepartment(deptId) {
  currentDepartmentId = deptId;
  const dept = departments.find(d => d.id === deptId);
  
  if (!dept) return;
  
  // Update current index for carousel
  currentDeptIndex = departments.findIndex(d => d.id === deptId);
  
  // Update department header
  document.getElementById('dept-name').textContent = dept.name;
  document.getElementById('dept-description').textContent = dept.description;
  
  // Update department logo
  const logoElement = document.getElementById('dept-logo');
  if (logoElement && dept.logo) {
    logoElement.src = dept.logo;
    logoElement.alt = dept.name + ' Logo';
  }
  
  // Update carousel background image
  const backgroundElement = document.getElementById('carousel-background');
  if (backgroundElement && dept.backgroundImage) {
    backgroundElement.style.backgroundImage = `url('${dept.backgroundImage}')`;
  }
  
  // Update active sidebar card
  document.querySelectorAll('.sidebar-card').forEach(card => {
    card.classList.remove('active');
    if (parseInt(card.getAttribute('data-dept')) === deptId) {
      card.classList.add('active');
    }
  });
  
  // Filter and display programs
  displayDepartmentPrograms(deptId);
  
  // Update URL without reload
  const newUrl = `${window.location.pathname}?dept=${deptId}`;
  window.history.pushState({ deptId }, '', newUrl);
}

// Display programs for a department
function displayDepartmentPrograms(deptId) {
  const container = document.getElementById('articles-container');
  const dept = departments.find(d => d.id === deptId);
  
  if (!dept) {
    container.innerHTML = '<div class="no-programs">Department not found.</div>';
    return;
  }
  
  // Check if programs are loaded yet
  if (allPrograms.length === 0) {
    container.innerHTML = '<div class="loading-message">Loading programs...</div>';
    return;
  }
  
  // Filter programs by department
  const deptPrograms = allPrograms.filter(program => {
    // Check if program.department matches the department name
    return program.department === dept.name;
  });
  
  console.log('Displaying', deptPrograms.length, 'programs for', dept.name);
  
  if (deptPrograms.length === 0) {
    container.innerHTML = '<div class="no-programs">No programs available for this department yet.</div>';
    return;
  }
  
  const html = deptPrograms.map(program => {
    const imageSource = program.images && program.images.length > 0 
      ? program.images[0].image_url 
      : '../images/placeholder.jpg';
    
    return `
      <div class="article-card" data-program-id="${program.id}">
        <img src="${imageSource}" alt="${program.program_name}">
        <h4>${program.program_name}</h4>
        <p>${truncateText(program.description || 'No description available.', 100)}</p>
        <div class="program-details">
          <small><strong>Location:</strong> ${program.location || '—'}</small><br>
          <small><strong>Status:</strong> ${program.status || '—'}</small>
        </div>
        <button class="read-more-btn" data-program-id="${program.id}">Read More</button>
      </div>
    `;
  }).join('');
  
  container.innerHTML = html;
  
  // Add click handlers
  container.querySelectorAll('.read-more-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const programId = this.getAttribute('data-program-id');
      const program = allPrograms.find(p => String(p.id) === String(programId));
      if (program) openProgramModal(program);
    });
  });
}

// Truncate text helper
function truncateText(text, maxLength) {
  if (text.length <= maxLength) return text;
  const truncated = text.substring(0, maxLength);
  const lastSpace = truncated.lastIndexOf(' ');
  return (lastSpace > 0 ? truncated.substring(0, lastSpace) : truncated) + '...';
}

// Modal functionality
let modalState = { images: [], index: 0 };

function openProgramModal(program) {
  const overlay = document.getElementById('program-modal-overlay');
  const modal = document.getElementById('program-modal');
  const title = document.getElementById('program-modal-title');
  const desc = document.getElementById('program-modal-description');
  const dept = document.getElementById('program-modal-dept');
  const loc = document.getElementById('program-modal-loc');
  const status = document.getElementById('program-modal-status');
  const mainImg = document.getElementById('modal-main-img');
  const thumbs = document.getElementById('modal-thumbs');

  title.textContent = program.program_name;
  desc.textContent = program.description || 'No description available.';
  dept.textContent = program.department || '—';
  loc.textContent = program.location || '—';
  status.textContent = program.status || '—';

  // Setup images
  if (program.images && program.images.length > 0) {
    modalState.images = program.images.map(imageObj => ({
      url: imageObj.image_url,
      alt: imageObj.image_desc || 'Program image'
    }));
  } else {
    modalState.images = [{
      url: '../images/placeholder.jpg',
      alt: 'No image available'
    }];
  }
  
  modalState.index = 0;
  
  // Set main image
  const firstImg = modalState.images[0];
  mainImg.src = firstImg.url;
  mainImg.alt = firstImg.alt;
  
  // Build thumbnails
  thumbs.innerHTML = '';
  modalState.images.forEach((imgObj, i) => {
    const thumb = document.createElement('img');
    thumb.src = imgObj.url;
    thumb.alt = imgObj.alt;
    thumb.dataset.idx = String(i);
    if (i === 0) thumb.classList.add('active');
    thumb.addEventListener('click', function() {
      setModalImage(parseInt(this.dataset.idx));
    });
    thumbs.appendChild(thumb);
  });

  overlay.classList.add('active');
  modal.classList.add('active');
}

function setModalImage(idx) {
  if (idx < 0) idx = modalState.images.length - 1;
  if (idx >= modalState.images.length) idx = 0;
  modalState.index = idx;
  
  const mainImg = document.getElementById('modal-main-img');
  mainImg.src = modalState.images[idx].url;
  mainImg.alt = modalState.images[idx].alt;
  
  const thumbs = document.getElementById('modal-thumbs');
  thumbs.querySelectorAll('img').forEach((img, i) => {
    if (i === idx) img.classList.add('active');
    else img.classList.remove('active');
  });
}

function closeProgramModal() {
  document.getElementById('program-modal-overlay').classList.remove('active');
  document.getElementById('program-modal').classList.remove('active');
}

// Modal controls
document.getElementById('program-modal-close').addEventListener('click', closeProgramModal);
document.getElementById('program-modal-overlay').addEventListener('click', closeProgramModal);
document.getElementById('modal-prev').addEventListener('click', function() {
  setModalImage(modalState.index - 1);
});
document.getElementById('modal-next').addEventListener('click', function() {
  setModalImage(modalState.index + 1);
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
  const isOpen = document.getElementById('program-modal').classList.contains('active');
  if (!isOpen) return;
  
  if (e.key === 'Escape') closeProgramModal();
  if (e.key === 'ArrowLeft') setModalImage(modalState.index - 1);
  if (e.key === 'ArrowRight') setModalImage(modalState.index + 1);
});

// Handle browser back/forward
window.addEventListener('popstate', function(e) {
  if (e.state && e.state.deptId) {
    loadDepartment(e.state.deptId);
  }
});