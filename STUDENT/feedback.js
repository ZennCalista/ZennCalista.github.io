document.addEventListener("DOMContentLoaded", function () {
  let currentPage = 1;

  // Helper function to convert expectations values to labels
  function getExpectationsLabel(value) {
    const labels = {
      '5': 'Far Exceeded',
      '4': 'Exceeded', 
      '3': 'Met',
      '2': 'Below',
      '1': 'Far Below'
    };
    return labels[value] || value;
  }

  // Function to load evaluations with pagination
  function loadEvaluations(page = 1) {
    fetch(`get_evaluations.php?page=${page}`)
      .then(res => res.json())
      .then(data => {
        // Overview
        document.getElementById('total-evals').textContent = data.total_evaluations;

        // Table
        const tbody = document.getElementById('eval-table-body');
        tbody.innerHTML = '';
        data.programs.forEach(row => {
          const tr = document.createElement('tr');
          const statusClass = row.status.toLowerCase();
          tr.innerHTML = `
            <td>${row.program_name}</td>
            <td><span class="status-badge status-${statusClass}">${row.status}</span></td>
            <td>${row.submitted_date || ''}</td>
            <td>
              ${row.can_evaluate
                ? `<button class="eval-btn" data-pid="${row.program_id}" data-pname="${row.program_name}">Evaluate</button>`
                : `<button class="evaluated-btn" disabled>Evaluated</button>`
              }
            </td>
          `;
          tbody.appendChild(tr);
        });

        // Button event
        document.querySelectorAll('.eval-btn').forEach(btn => {
          btn.onclick = function() {
            openDetailedEvalModal(
              btn.getAttribute('data-pid'),
              btn.getAttribute('data-pname')
            );
          };
        });

        // Render pagination
        renderPagination(data.pagination);
      });
  }

  // Function to render pagination controls
  function renderPagination(pagination) {
    const existingPagination = document.querySelector('.pagination-container');
    if (existingPagination) {
      existingPagination.remove();
    }

    if (pagination.total_pages <= 1) return;

    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'pagination-container';
    paginationContainer.style.cssText = 'display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; padding: 20px;';

    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.textContent = 'Previous';
    prevBtn.style.cssText = 'padding: 8px 16px; border: 1px solid #ddd; background: #fff; cursor: pointer; border-radius: 4px;';
    prevBtn.disabled = pagination.current_page === 1;
    if (prevBtn.disabled) {
      prevBtn.style.opacity = '0.5';
      prevBtn.style.cursor = 'not-allowed';
    }
    prevBtn.onclick = () => {
      if (pagination.current_page > 1) {
        currentPage = pagination.current_page - 1;
        loadEvaluations(currentPage);
      }
    };
    paginationContainer.appendChild(prevBtn);

    // Page numbers
    const pageInfo = document.createElement('span');
    pageInfo.textContent = `Page ${pagination.current_page} of ${pagination.total_pages}`;
    pageInfo.style.cssText = 'margin: 0 15px; font-weight: 500;';
    paginationContainer.appendChild(pageInfo);

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.textContent = 'Next';
    nextBtn.style.cssText = 'padding: 8px 16px; border: 1px solid #ddd; background: #fff; cursor: pointer; border-radius: 4px;';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    if (nextBtn.disabled) {
      nextBtn.style.opacity = '0.5';
      nextBtn.style.cursor = 'not-allowed';
    }
    nextBtn.onclick = () => {
      if (pagination.current_page < pagination.total_pages) {
        currentPage = pagination.current_page + 1;
        loadEvaluations(currentPage);
      }
    };
    paginationContainer.appendChild(nextBtn);

    // Insert pagination after table
    const tableWrapper = document.querySelector('.program-eval-section .table-wrapper');
    tableWrapper.parentNode.insertBefore(paginationContainer, tableWrapper.nextSibling);
  }

  // Initial load
  loadEvaluations(currentPage);

  // Show the detailed evaluation modal
  function openDetailedEvalModal(programId, programName) {
    document.getElementById('detailed-program-id').value = programId;
    document.getElementById('modal-program-title').textContent = programName;
    document.getElementById('detailed-eval-form').reset();
    document.getElementById('detailed-eval-message').textContent = '';
    document.getElementById('suggestion-other-text').style.display = 'none';
    document.getElementById('detailed-eval-modal').style.display = 'block';
    
    // Toggle "Other" textarea based on dropdown selection
    const suggestionDropdown = document.getElementById('suggestion-dropdown');
    suggestionDropdown.onchange = function() {
      const otherTextarea = document.getElementById('suggestion-other-text');
      if (this.value === 'other') {
        otherTextarea.style.display = 'block';
        otherTextarea.required = true;
      } else {
        otherTextarea.style.display = 'none';
        otherTextarea.required = false;
        otherTextarea.value = '';
      }
    };
  }

  // Close modal logic
  document.getElementById('close-detailed-eval-modal').onclick = function() {
    document.getElementById('detailed-eval-modal').style.display = 'none';
  };

  // Close when clicking outside the modal content
  document.getElementById('detailed-eval-modal').onclick = function(event) {
    if (event.target === this) {
      this.style.display = 'none';
    }
  };

  // Attach to Evaluate buttons (after you render the table)
  document.querySelectorAll('.eval-btn').forEach(btn => {
    btn.onclick = function() {
      openDetailedEvalModal(
        btn.getAttribute('data-pid'),
        btn.getAttribute('data-pname')
      );
    };
  });

  document.getElementById('detailed-eval-form').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const suggestionDropdown = form.querySelector('[name="suggestion-dropdown"]');
    const suggestionOther = form.querySelector('[name="suggestion-other"]');
    const suggestionValue = suggestionDropdown.value === 'other' 
      ? suggestionOther.value 
      : suggestionDropdown.value;
    
    const data = {
      program_id: form.program_id.value,
      content: form.content.value,
      facilitators: form.facilitators.value,
      relevance: form.relevance.value,
      organization: form.organization.value,
      experience: form.experience.value,
      suggestion: suggestionValue,
      expectations_met: form.expectations_met.value
    };
    fetch('submit_detailed_evaluation.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(resp => {
      document.getElementById('detailed-eval-message').textContent = resp.message;
      // After successful submit
      if (resp.status === 'success') {
        setTimeout(() => {
          document.getElementById('detailed-eval-modal').style.display = 'none';
          location.reload(); // This will reload and update the table
        }, 1000);
      }
    });
  };

  document.getElementById('view-all-evals').onclick = function(e) {
  e.preventDefault();
  fetch('get_all_evaluations.php')
    .then(res => res.json())
    .then(data => {
      console.log(data);
      const section = document.querySelector('.all-evals-section');
      const tbody = section.querySelector('#all-evals-table-body');
      tbody.innerHTML = '';
      data.evaluations.forEach(ev => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${ev.program_name}</td>
          <td>${ev.eval_date || ''}</td>
          <td>${ev.content}</td>
          <td>${ev.facilitators}</td>
          <td>${ev.relevance}</td>
          <td>${ev.organization}</td>
          <td>${ev.experience}</td>
          <td>${ev.suggestion || ''}</td>
          <td>${getExpectationsLabel(ev.expectations_met) || ''}</td>
        `;
        tbody.appendChild(tr);
      });
      section.style.display = 'block'; // Show the section
    });
};
  // Close modal logic
  document.getElementById('close-all-evals-modal').onclick = function() {
    document.getElementById('all-evals-modal').style.display = 'none';
  };
  // Close when clicking outside the modal content
  document.getElementById('all-evals-modal').onclick = function(event) {
    if (event.target === this) {
      this.style.display = 'none';
    }
  };
});