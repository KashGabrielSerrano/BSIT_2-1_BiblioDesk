<?php
require_once __DIR__ . "/core/bootstrap.php";
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Logged in and still Active (re-checked against the DB), otherwise -> login.php
$auth = require_login_page();

$con = db();
$so  = so();

$book_id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id    = $auth['user_id'];   // 
$user_role  = $auth['role'];
$user_email = $_SESSION['email'] ?? '';

// Fetch book details
$stmt = $con->prepare("SELECT Title, Drive_Link FROM book_table WHERE Book_ID = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book || empty($book['Drive_Link'])) {
    exit("Book or digital file not found in system catalog.");
}

// Record Reading History (STEP #1)
$hist_stmt = $con->prepare("INSERT INTO user_reading_history (User_ID, Book_ID, Last_Opened) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE Last_Opened = NOW()");
$hist_stmt->bind_param("ii", $user_id, $book_id);
$hist_stmt->execute();
$hist_stmt->close();

// Fetch saved reading page progress if available (STEP #2)
$prog_stmt = $con->prepare("SELECT Current_Page, Is_Finished FROM user_reading_progress WHERE User_ID = ? AND Book_ID = ?");
$prog_stmt->bind_param("ii", $user_id, $book_id);
$prog_stmt->execute();
$prog_res = $prog_stmt->get_result()->fetch_assoc();
$prog_stmt->close();

$initial_page = 1;
if ($prog_res && $prog_res['Is_Finished'] == 0 && $prog_res['Current_Page'] > 1) {
    $initial_page = (int)$prog_res['Current_Page'];
}

// Generate single-use streaming token valid for 60 seconds
$token = bin2hex(random_bytes(32));
if (!isset($_SESSION['image_tokens'])) {
    $_SESSION['image_tokens'] = [];
}
$_SESSION['image_tokens'][$token] = [
    'user_id'    => $user_id,
    'book_id'    => $book_id,
    'expires_at' => time() + 60
];

// Session token for API & CSRF
$csrf_token = $_SESSION['csrf_token'] ?? '';

session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BiblioDesk Document Reader - <?php echo htmlspecialchars($book['Title']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <!-- QR code for the Share panel is drawn in the browser (no third-party service sees the link) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            margin: 0;
            background-color: #2b2b2b;
            color: #ffffff;
            font-family: Arial, sans-serif;
            user-select: none;
            -webkit-user-select: none;
        }
        #viewer-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 12px;
            background: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }
        #viewer-toolbar button {
            background-color: #3F4A33;
            color: #ffffff;
            border: 1px solid #555;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        #viewer-toolbar button:disabled {
            background-color: #555;
            cursor: not-allowed;
        }
        #page-input {
            width: 45px;
            background: #333;
            color: #fff;
            border: 1px solid #555;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            padding: 4px;
            font-size: 14px;
        }
        #page-input::-webkit-inner-spin-button, 
        #page-input::-webkit-outer-spin-button { 
            -webkit-appearance: none;
            margin: 0;
        }
        #viewer-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            min-height: calc(100vh - 80px);
            position: relative;
        }
        #page-canvas {
            display: none; 
            max-width: 95%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            background: #ffffff;
        }
        .spinner {
            border: 5px solid rgba(255, 255, 255, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border-left-color: #8E9A78;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #loading-overlay {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            color: #ccc;
            font-size: 14px;
        }
        /* Share panel (link + QR back to this book) and staff preview badge */
        #viewer-toolbar { flex-wrap: wrap; }
        #share-panel {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            width: min(360px, calc(100vw - 24px));
            background: #1a1a1a;
            border: 1px solid #555;
            border-radius: 8px;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.5);
            text-align: center;
        }
        #share-panel.open { display: block; }
        #share-qr { display: inline-block; background: #fff; padding: 8px; border-radius: 6px; }
        #share-panel p { font-size: 12px; color: #ccc; margin: 10px 0 6px; }
        #share-row { display: flex; gap: 6px; }
        #share-link {
            flex: 1; min-width: 0; background: #333; color: #fff; border: 1px solid #555;
            border-radius: 4px; padding: 5px 8px; font-size: 12px; user-select: all;
        }
        .preview-badge {
            background: #ffc107; color: #1a1a1a; border-radius: 999px; padding: 4px 10px;
            font-size: 12px; font-weight: bold; text-decoration: none;
        }
        @media print {
            body { display: none !important; }
            #viewer-container, #page-canvas { display: none !important; }
        }
    </style>
</head>
<body>
<div id="viewer-toolbar">
    <button id="prev-btn" disabled>Previous</button>
    <span>Page: 
        <input type="number" id="page-input" value="1" min="1" disabled> 
        / <span id="total-pages">-</span>
    </span>
    <button id="next-btn" disabled>Next</button>
    <button id="share-btn" type="button" aria-expanded="false" aria-controls="share-panel">Share</button>
<?php if (in_array($user_role, ['Librarian', 'Admin'], true)): ?>
    <a class="preview-badge" href="librarymanagement.php" title="Back to Library Management">Preview mode (<?php echo htmlspecialchars($user_role); ?>)</a>
<?php endif; ?>
    <div id="share-panel" role="dialog" aria-label="Share this book">
        <div id="share-qr"></div>
        <p>Link back to this book (readers need to be logged in)</p>
        <div id="share-row">
            <input type="text" id="share-link" readonly>
            <button id="share-copy" type="button">Copy</button>
        </div>
    </div>
</div>

<div id="viewer-container">
    <div id="loading-overlay">
        <div class="spinner"></div>
        <span>Decrypting and loading document stream...</span>
    </div>
    <canvas id="page-canvas"></canvas>
</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const authToken = <?php echo json_encode($token); ?>;
const userEmail = <?php echo json_encode($user_email); ?>;
const bookId = <?php echo json_encode($book_id); ?>;
const initialSavedPage = <?php echo json_encode($initial_page); ?>;
const csrfToken = <?php echo json_encode($csrf_token); ?>;
const appBaseUrl = <?php echo json_encode(APP_BASE_URL); ?>;

(() => {
    const btn = document.getElementById('share-btn');
    const panel = document.getElementById('share-panel');
    const qrBox = document.getElementById('share-qr');
    const linkBox = document.getElementById('share-link');
    const copyBtn = document.getElementById('share-copy');
    btn.addEventListener('click', () => {
        const open = !panel.classList.contains('open');
        if (open) {
            const url = new URL('read_book.php?id=' + encodeURIComponent(bookId), appBaseUrl || window.location.href).href;
            linkBox.value = url;
            qrBox.replaceChildren();
            if (typeof QRCode !== 'undefined') {
                new QRCode(qrBox, { text: url, width: 128, height: 128, correctLevel: QRCode.CorrectLevel.M });
                qrBox.style.display = '';
            } else {
                qrBox.style.display = 'none';   // QR library did not load: the link still works
            }
        }
        panel.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
    });
    copyBtn.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(linkBox.value); }
        catch { linkBox.select(); document.execCommand('copy'); }
        copyBtn.textContent = 'Copied';
        setTimeout(() => { copyBtn.textContent = 'Copy'; }, 1500);
    });
})();

let pdfDoc = null;
let currentPage = initialSavedPage;
let isRendering = false;
let pendingPage = null;
let progressSaveTimeout = null;

const canvas = document.getElementById('page-canvas');
const ctx = canvas.getContext('2d');
const loadingOverlay = document.getElementById('loading-overlay');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const pageInput = document.getElementById('page-input');

document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) || (e.ctrlKey && e.key.toUpperCase() === 'U')) {
        e.preventDefault();
    }
});

function showLoadError(message) {
    loadingOverlay.replaceChildren();
    const p = document.createElement('p');
    p.style.color = '#e70808';
    p.textContent = message;
    loadingOverlay.appendChild(p);
}

function saveReadingProgress(page, total) {
    if (!pdfDoc) return;
    clearTimeout(progressSaveTimeout);
    progressSaveTimeout = setTimeout(() => {
        const formData = new FormData();
        formData.append('action', 'save_progress');
        formData.append('book_id', bookId);
        formData.append('current_page', page);
        formData.append('total_pages', total);
        formData.append('csrf_token', csrfToken);

        fetch('../backend/user_librarydb_controller.php', {
            method: 'POST',
            body: formData
        }).catch(err => console.error("Failed to save progress:", err));
    }, 1000); // 1-second debounce
}

async function loadDocument() {
    try {
        const res = await fetch('stream_pdf.php', {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'BiblioDeskReader'
            },
            body: new URLSearchParams({ token: authToken })
        });
        if (!res.ok) {
            throw new Error((await res.text()) || ('HTTP ' + res.status));
        }
        const bin = atob((await res.text()).trim());
        const data = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) {
            data[i] = bin.charCodeAt(i);
        }
        pdfDoc = await pdfjsLib.getDocument({ data: data }).promise;
        document.getElementById('total-pages').textContent = pdfDoc.numPages;
        pageInput.max = pdfDoc.numPages;

        if (currentPage > pdfDoc.numPages) currentPage = 1;

        await renderPage(currentPage);
        loadingOverlay.style.display = 'none';
        canvas.style.display = 'block';
        prevBtn.disabled = false;
        nextBtn.disabled = false;
        pageInput.disabled = false;
    } catch (err) {
        console.error("Failed to load PDF document stream:", err);
        showLoadError('Failed to load document stream. Please go back and reopen the book.');
    }
}

async function renderPage(pageNum) {
    if (!pdfDoc) return;
    if (isRendering) {
        pendingPage = pageNum;
        return;
    }
    isRendering = true;
    try {
        const page = await pdfDoc.getPage(pageNum);
        const viewport = page.getViewport({ scale: 1.5 });
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        await page.render({ canvasContext: ctx, viewport: viewport }).promise;
        if (userEmail) {
            ctx.font = '18px Arial';
            ctx.fillStyle = 'rgba(150, 150, 150, 0.35)';
            ctx.fillText(`Licensed to: ${userEmail}`, 20, canvas.height - 20);
        }
        currentPage = pageNum;
        pageInput.value = pageNum;
        saveReadingProgress(pageNum, pdfDoc.numPages);
    } catch (err) {
        console.error("Render error:", err);
    } finally {
        isRendering = false;
        if (pendingPage !== null) {
            const next = pendingPage;
            pendingPage = null;
            renderPage(next);
        }
    }
}

prevBtn.addEventListener('click', () => {
    const target = pendingPage ?? currentPage;
    if (target > 1) renderPage(target - 1);
});

nextBtn.addEventListener('click', () => {
    const target = pendingPage ?? currentPage;
    if (pdfDoc && target < pdfDoc.numPages) renderPage(target + 1);
});

function handlePageInputChange() {
    if (!pdfDoc) return;
    let targetPage = parseInt(pageInput.value, 10);
    if (isNaN(targetPage) || targetPage < 1) {
        targetPage = 1;
    } else if (targetPage > pdfDoc.numPages) {
        targetPage = pdfDoc.numPages;
    }
    renderPage(targetPage);
}

pageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        pageInput.blur();
    }
});

pageInput.addEventListener('change', handlePageInputChange);

loadDocument();
</script>
</body>
</html>