<?php
session_start();
require_once "connection/config.php";
require_once "helpers/SystemOperators.php";

$con = connection();$so  = new SystemOperators();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$book_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id   =$_SESSION['user_id'];
$user_role =$_SESSION['role'] ?? 'Student';

$stmt =$con->prepare("SELECT Title FROM book_table WHERE Book_ID = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();$book = $stmt->get_result()->fetch_assoc();$stmt->close();

if (!$book) {
    exit("Book not found in system catalog.");
}

if ($user_role !== 'Admin') {
    $auth_stmt =$con->prepare("SELECT Reservation_ID FROM reservation_table WHERE User_ID = ? AND Book_ID = ? AND Request_approval = 'Approved' UNION 
                                SELECT Transaction_ID FROM transaction_table WHERE User_ID = ? AND Book_ID = ? AND Return_Date IS NULL
                            ");
    $auth_stmt->bind_param("iiii", $user_id,$book_id, $user_id,$book_id);
    $auth_stmt->execute();$has_access = ($auth_stmt->get_result()->num_rows > 0);$auth_stmt->close();

    if (!$has_access) {
        header("Location: librarydb.php");
        exit();
    }
}
$token = bin2hex(random_bytes(32));
if (!isset($_SESSION['image_tokens'])) {$_SESSION['image_tokens'] = [];
}
$_SESSION['image_tokens'][$token] = [
    'user_id'    => $user_id,
    'book_id'    => $book_id,
    'expires_at' => time() + 10
];
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
            body {
            display: none !important;
        }
        #viewer-container, #page-canvas {
        display: none !important;
        }
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
const userEmail = <?php echo json_encode($_SESSION['email'] ?? ''); ?>;

let pdfDoc = null;
let currentPage = 1;
let isRendering = false;

const canvas = document.getElementById('page-canvas');
const ctx = canvas.getContext('2d');
const loadingOverlay = document.getElementById('loading-overlay');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const pageInput = document.getElementById('page-input');


setInterval(() => {
    const startTime = performance.now();
    debugger; 
    const endTime = performance.now();
    if (endTime - startTime > 100) {
        document.body.innerHTML = '<h1 style="color:red; text-align:center; margin-top:20%;">Developer Tools Detected. Session Interrupted.</h1>';
        setTimeout(() => { window.location.href = 'librarydb.php'; }, 1500);
    }
}, 1000);

document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) || (e.ctrlKey && e.key.toUpperCase() === 'U')) {
        e.preventDefault();
    }
});

document.addEventListener('keyup', (e) => {
    if (e.key === 'PrintScreen') {
        navigator.clipboard.writeText('Screenshots are restricted on BiblioDesk LMS.');
        alert('Screenshots are restricted on this platform.');
    }
});

async function loadDocument() {
    try {
        const pdfUrl = `stream_pdf.php?token=${authToken}`;
        
        pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;
        document.getElementById('total-pages').textContent = pdfDoc.numPages;
        pageInput.max = pdfDoc.numPages;

        // Render first page and remove loading overlay
        await renderPage(currentPage);
        
        loadingOverlay.style.display = 'none';
        canvas.style.display = 'block';
        
        prevBtn.disabled = false;
        nextBtn.disabled = false;
        pageInput.disabled = false;
    } catch (err) {
        console.error("Failed to load PDF document stream:", err);
        loadingOverlay.innerHTML = '<p style="color:#e70808;">Failed to load document stream. Access token may be expired.</p>';
    }
}

async function renderPage(pageNum) {
    if (isRendering || !pdfDoc) return;
    isRendering = true;

    try {
        const page = await pdfDoc.getPage(pageNum);
        const viewport = page.getViewport({ scale: 1.5 });

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };

        await page.render(renderContext).promise;

        if (userEmail) {
            ctx.font = '18px Arial';
            ctx.fillStyle = 'rgba(150, 150, 150, 0.35)';
            ctx.fillText(`Licensed to: ${userEmail}`, 20, canvas.height - 20);
        }

        pageInput.value = pageNum;
    } catch (err) {
        console.error("Render error:", err);
    } finally {
        isRendering = false;
    }
}

prevBtn.addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
    }
});

nextBtn.addEventListener('click', () => {
    if (pdfDoc && currentPage < pdfDoc.numPages) {
        currentPage++;
        renderPage(currentPage);
    }
});

function handlePageInputChange() {
    if (!pdfDoc) return;
    let targetPage = parseInt(pageInput.value, 10);

    if (isNaN(targetPage) || targetPage < 1) {
        targetPage = 1;
    } else if (targetPage > pdfDoc.numPages) {
        targetPage = pdfDoc.numPages;
    }

    currentPage = targetPage;
    renderPage(currentPage);
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