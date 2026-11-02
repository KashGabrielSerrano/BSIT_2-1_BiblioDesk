<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";

$con = connection();
$so  = new SystemOperators();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$book_id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id    = $_SESSION['user_id'];
$user_role  = $_SESSION['role'] ?? 'Student';
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

// Record Reading History (BEHAVIOR #1)
$hist_stmt = $con->prepare("INSERT INTO user_reading_history (User_ID, Book_ID, Last_Opened) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE Last_Opened = NOW()");
$hist_stmt->bind_param("ii", $user_id, $book_id);
$hist_stmt->execute();
$hist_stmt->close();

// Fetch saved reading page progress if available (BEHAVIOR #2)
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

        fetch('mybooks_api.php', {
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