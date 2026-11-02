document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('addBookForm');
    const selectAll = document.getElementById('selectAllCheckbox');
    const bulkBtn = document.getElementById('bulkDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Select All
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            let checkboxes = document.querySelectorAll('.rowCheckbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    //Save & Publish Form 
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var successModalElement = document.getElementById('successBookModal');
            if (successModalElement) {
                var successModal = bootstrap.Modal.getOrCreateInstance(successModalElement);
                successModal.show();
                form.reset();
            }
        });
    }

//The delete selected
    if (bulkBtn) {
        bulkBtn.addEventListener('click', function () {
            let checkedBoxes = document.querySelectorAll('.rowCheckbox:checked');

            if (checkedBoxes.length === 0) {
                alert('Please select at least one book to delete.');
                return;
            }
            var deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmDeleteModal'));
            deleteModal.show();
        });
    }

    //Confirm Delete 
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            var deleteModalEl = document.getElementById('confirmDeleteModal');
            var deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
            if (deleteModal) {
                deleteModal.hide();
            }

            let checkedBoxes = document.querySelectorAll('.rowCheckbox:checked');
            checkedBoxes.forEach(cb => {
                let row = cb.closest('tr');
                if (row) row.remove();
            });

            if (selectAll) selectAll.checked = false;

            var successDeleteModalEl = document.getElementById('successDeleteModal');
            if (successDeleteModalEl) {
                var successDeleteModal = bootstrap.Modal.getOrCreateInstance(successDeleteModalEl);
                successDeleteModal.show();
            }
        });
    }
});