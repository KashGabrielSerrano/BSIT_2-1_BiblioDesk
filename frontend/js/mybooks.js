document.addEventListener('DOMContentLoaded', () => {
    const bookModalEl = document.getElementById('bookDetailsModal');
    if (!bookModalEl) return;

    const bsModal = new bootstrap.Modal(bookModalEl);
    
    const modalImg = document.getElementById('modalBookImg');
    const modalTitle = document.getElementById('modalBookTitle');
    const modalAuthor = document.getElementById('modalBookAuthor');
    const modalIsbn = document.getElementById('modalBookIsbn');
    const modalGenre = document.getElementById('modalBookGenre');
    const modalDesc = document.getElementById('modalBookDesc');
    const modalPublisherName = document.getElementById('modalBookPublisherName');
    const modalBookId = document.getElementById('modalBookId');
    const modalReadBtn = document.getElementById('modalBookRead');
    const modalAddToColl = document.getElementById('modalBookAddToCollections');
    const modalBookmarkBtn = document.getElementById('modalBookBookmark');
    const modalFavoriteBtn = document.getElementById('modalBookFavorite');

    const csrfToken = window.csrfToken || '';

    // Delegate click for all book triggers (.book-trigger)
    document.body.addEventListener('click', (e) => {
        const trigger = e.target.closest('.book-trigger');
        if (!trigger) return;
        e.preventDefault();

        const id = trigger.dataset.id || '';
        const title = trigger.dataset.title || 'Untitled';
        const author = trigger.dataset.author || 'Unknown Author';
        const desc = trigger.dataset.desc || 'No description available.';
        const isbn = trigger.dataset.isbn || 'N/A';
        const genre = trigger.dataset.genre || 'General';
        const publisher = trigger.dataset.publisher || 'N/A';
        const hasFile = trigger.dataset.hasfile === '1';
        const cover = trigger.dataset.cover || '../frontend/assets/icons/library-default.png';

        // Populate Modal Fields (Consistent with user_librarydb.php)
        modalBookId.value = id;
        if (modalTitle) modalTitle.textContent = title;
        if (modalAuthor) modalAuthor.textContent = author;
        if (modalDesc) modalDesc.textContent = desc;
        if (modalIsbn) modalIsbn.textContent = 'ISBN: ' + isbn;
        if (modalGenre) modalGenre.textContent = genre;
        if (modalPublisherName) modalPublisherName.textContent = publisher;
        if (modalImg) modalImg.src = cover;

        // Reset default button visibility
        if (hasFile && modalReadBtn) {
            modalReadBtn.classList.remove('d-none');
            modalReadBtn.href = `read_book.php?id=${id}`;
            modalReadBtn.textContent = 'Read Now';
        } else if (modalReadBtn) {
            modalReadBtn.classList.add('d-none');
        }

        if (modalAddToColl) modalAddToColl.classList.remove('d-none');
        if (modalBookmarkBtn) modalBookmarkBtn.classList.remove('d-none');
        if (modalFavoriteBtn) modalFavoriteBtn.classList.remove('d-none');

        // Reset icons to outline state while fetching database status
        updateBookmarkIcon(false);
        updateFavoriteIcon(false);

        // Fetch user state (Bookmark, Favorite, Reading Progress)
        if (id) {
            fetch(`mybooks_api.php?action=get_book_status&book_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateBookmarkIcon(data.bookmarked);
                        updateFavoriteIcon(data.favorited);
                        
                        if (hasFile && data.has_progress && modalReadBtn) {
                            modalReadBtn.textContent = 'Continue Reading';
                        }
                    }
                })
                .catch(err => console.error("Error fetching book status:", err));
        }

        bsModal.show();
    });

    function updateBookmarkIcon(isBookmarked) {
        if (!modalBookmarkBtn) return;
        const icon = modalBookmarkBtn.querySelector('i');
        if (icon) {
            icon.className = isBookmarked ? 'bi bi-bookmark-fill fs-4 text-warning' : 'bi bi-bookmark fs-4';
        }
    }

    function updateFavoriteIcon(isFavorited) {
        if (!modalFavoriteBtn) return;
        const icon = modalFavoriteBtn.querySelector('i');
        if (icon) {
            icon.className = isFavorited ? 'bi bi-heart-fill fs-4 text-danger' : 'bi bi-heart fs-4';
        }
    }

    // Toggle Bookmark Event
    if (modalBookmarkBtn) {
        modalBookmarkBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const bookId = modalBookId.value;
            if (!bookId) return;

            const formData = new FormData();
            formData.append('action', 'toggle_bookmark');
            formData.append('book_id', bookId);
            formData.append('csrf_token', csrfToken);

            fetch('mybooks_api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateBookmarkIcon(data.bookmarked);
                    }
                })
                .catch(err => console.error("Error toggling bookmark:", err));
        });
    }

    // Toggle Favorite Event
    if (modalFavoriteBtn) {
        modalFavoriteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const bookId = modalBookId.value;
            if (!bookId) return;

            const formData = new FormData();
            formData.append('action', 'toggle_favorite');
            formData.append('book_id', bookId);
            formData.append('csrf_token', csrfToken);

            fetch('../backend/user_librarydb_controller.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateFavoriteIcon(data.favorited);
                    }
                })
                .catch(err => console.error("Error toggling favorite:", err));
        });
    }
});