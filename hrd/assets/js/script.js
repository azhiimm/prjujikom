document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Initialize dropdowns
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const dropdownBtn = dropdown.querySelector('.dropdown-btn');
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        
        if (dropdownBtn && dropdownContent) {
            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownContent.classList.toggle('show');
            });
        }
    });
    
    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', function() {
        const dropdownContents = document.querySelectorAll('.dropdown-content');
        dropdownContents.forEach(content => {
            if (content.classList.contains('show')) {
                content.classList.remove('show');
            }
        });
    });
    
    // Filter functionality
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('reset', function() {
            setTimeout(() => {
                this.submit();
            }, 10);
        });
    }
    
    // Confirm delete
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle history details
    const historyToggles = document.querySelectorAll('.toggle-history');
    historyToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const historyDetails = this.nextElementSibling;
            historyDetails.style.display = historyDetails.style.display === 'none' ? 'block' : 'none';
            this.querySelector('i').classList.toggle('fa-chevron-down');
            this.querySelector('i').classList.toggle('fa-chevron-up');
        });
    });
    
    // Column selection for report
    const selectAllColumns = document.getElementById('select-all-columns');
    if (selectAllColumns) {
        selectAllColumns.addEventListener('change', function() {
            const columnCheckboxes = document.querySelectorAll('.column-checkbox');
            columnCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all checkbox state
        const columnCheckboxes = document.querySelectorAll('.column-checkbox');
        columnCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(columnCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(columnCheckboxes).some(cb => cb.checked);
                
                selectAllColumns.checked = allChecked;
                selectAllColumns.indeterminate = someChecked && !allChecked;
            });
        });
    }
    
    // Date picker initialization (if you have one)
    const datePickers = document.querySelectorAll('.date-picker');
    if (datePickers.length > 0) {
        datePickers.forEach(picker => {
            // If you have a date picker library, initialize it here
            // For native date inputs, nothing needs to be done
        });
    }
    
    // Add dynamic form fields for search filters
    const addFilterBtn = document.getElementById('add-filter');
    if (addFilterBtn) {
        addFilterBtn.addEventListener('click', function() {
            const filterContainer = document.getElementById('dynamic-filters');
            const filterCount = filterContainer.children.length;
            
            const newFilter = document.createElement('div');
            newFilter.className = 'filter-row';
            newFilter.innerHTML = `
                <div class="filter-group">
                    <select name="filter_field[]" class="filter-field">
                        <option value="">Pilih Field</option>
                        <option value="nama">Nama</option>
                        <option value="nip">NIP</option>
                        <option value="status">Status</option>
                        <option value="jabatan">Jabatan</option>
                        <option value="unit_kerja">Unit Kerja</option>
                        <option value="pendidikan">Pendidikan</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="filter_operator[]" class="filter-operator">
                        <option value="contains">Mengandung</option>
                        <option value="equals">Sama Dengan</option>
                        <option value="starts">Dimulai Dengan</option>
                        <option value="ends">Diakhiri Dengan</option>
                    </select>
                </div>
                <div class="filter-group">
                    <input type="text" name="filter_value[]" class="filter-value" placeholder="Nilai">
                </div>
                <div class="filter-group filter-action">
                    <button type="button" class="btn btn-danger btn-sm remove-filter">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            filterContainer.appendChild(newFilter);
            
            // Add event listener to remove button
            newFilter.querySelector('.remove-filter').addEventListener('click', function() {
                filterContainer.removeChild(newFilter);
            });
        });
    }
    
    // Initialize any remove filter buttons that exist at page load
    const removeFilterBtns = document.querySelectorAll('.remove-filter');
    removeFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterRow = this.closest('.filter-row');
            filterRow.parentNode.removeChild(filterRow);
        });
    });
    
    // Show/hide columns in report
    const columnToggle = document.getElementById('column-toggle');
    if (columnToggle) {
        columnToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const columnSelection = document.getElementById('column-selection');
            columnSelection.style.display = columnSelection.style.display === 'none' ? 'block' : 'none';
        });
    }
    
    // Export functionality
    const exportButtons = document.querySelectorAll('.export-btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const format = this.dataset.format;
            const form = document.getElementById('report-form');
            
            // Add hidden input for export format
            let exportInput = document.getElementById('export-format');
            if (!exportInput) {
                exportInput = document.createElement('input');
                exportInput.type = 'hidden';
                exportInput.id = 'export-format';
                exportInput.name = 'export';
                form.appendChild(exportInput);
            }
            
            exportInput.value = format;
            form.submit();
        });
    });
});