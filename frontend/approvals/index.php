<?php
require_once __DIR__ . '/../../backend/Core/Session.php';
require_once __DIR__ . '/../../backend/config/db.php';
require_once __DIR__ . '/../../backend/Repositories/PendingRepository.php';

startAuthSession();

if (empty($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Access Denied — Theatro INSAT</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body class="access-denied-body">
        <h1>This section is for admins only</h1>
        <p>You do not have permission to view this page.</p>
        <a href="/frontend/home/index.html">← Back to Home</a>
    </body>
    </html>
    <?php
    exit;
}

$repo = new PendingRepository();
$rows = $repo->findAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests — Theatro INSAT</title>
    <link rel="stylesheet" href="styles.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Cinzel:wght@400;600&family=Montserrat:wght@400;500&display=swap"
        rel="stylesheet">
</head>

<body>

    <nav class="navbar">
    <div class="logo">
        <img src="../assets/logo.png" alt="Theatro INSAT">
    </div>
<ul class="nav-links">
    <li><a href="/frontend/home/index.html">Home</a></li>
    <li><a href="/frontend/home/index.html#about">About</a></li>
    <li><a href="/frontend/home/index.html#shows">Shows</a></li>
    <li><a href="/frontend/joinus/index.html">Join Us</a></li>
    <li><a href="/frontend/contact/index.html">Contact</a></li>
</ul>
    <div class="nav-auth-wrapper">
        <button class="nav-logout-btn" id="logout-btn">Logout</button>
    </div>
</nav>

    <!-- BACKGROUND -->
    <div class="stars-bg"></div>
    <div class="stage-lights">
        <div class="spotlight spotlight-left"></div>
        <div class="spotlight spotlight-right"></div>
    </div>

    <!-- TITLE -->
    <h1 id="main-title">PENDING REQUESTS</h1>

    <!-- BADGE COUNT -->
    <div id="pending-badge" style="text-align:center; margin-top: 80px; margin-bottom: -20px;">
        <span style="
            display: inline-block;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(192,192,192,0.5);
            border: 1px solid rgba(192,192,192,0.15);
            padding: 6px 18px;
            border-radius: 20px;
        ">
            <?= count($rows) ?> request<?= count($rows) !== 1 ? 's' : '' ?> pending
        </span>
    </div>

    <!-- CARDS -->
    <div class="card-container">

        <?php if (empty($rows)): ?>

            <div class="card empty-card">
                <div class="card-content" style="text-align:center; padding: 20px 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="rgba(192,192,192,0.4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        style="display:block; margin: 0 auto 20px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="9" y1="13" x2="15" y2="13"/>
                    </svg>
                    <span class="label" style="display:block; margin-bottom: 10px; font-size: 11px; letter-spacing: 2px;">No requests</span>
                    <span class="value" style="font-size: 17px; color: rgba(200,200,200,0.6);">No pending registrations</span>
                </div>
            </div>

        <?php else: ?>

            <?php foreach ($rows as $row): ?>
                <div class="card" data-id="<?= htmlspecialchars($row['id']) ?>">

                    <!-- CARD HEADER -->
                    <div class="card-header" style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding-bottom: 16px;
                        margin-bottom: 14px;
                        border-bottom: 1px solid rgba(192,192,192,0.1);
                    ">
                        <?php if (!empty($row['picture'])): ?>
                            <img src="<?= htmlspecialchars($row['picture']) ?>" alt="avatar" style="
                                width: 42px; height: 42px;
                                border-radius: 50%;
                                object-fit: cover;
                                border: 1px solid rgba(192,192,192,0.2);
                                flex-shrink: 0;
                            ">
                        <?php else: ?>
                            <div style="
                                width: 42px; height: 42px;
                                border-radius: 50%;
                                background: rgba(192,192,192,0.08);
                                border: 1px solid rgba(192,192,192,0.2);
                                display: flex; align-items: center; justify-content: center;
                                font-family: 'Cinzel', serif;
                                font-size: 14px;
                                color: rgba(220,220,220,0.8);
                                flex-shrink: 0;
                            ">
                                <?= strtoupper(substr($row['firstname'] ?? '?', 0, 1) . substr($row['lastname'] ?? '', 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <div style="font-family:'Cinzel',serif; font-size:14px; color:rgba(230,230,230,0.95); letter-spacing:0.5px;">
                                <?= htmlspecialchars(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?>
                            </div>
                            <div style="font-family:'Montserrat',sans-serif; font-size:11px; color:rgba(192,192,192,0.5); margin-top:2px;">
                                <?= htmlspecialchars($row['email'] ?? '') ?>
                            </div>
                        </div>
                    </div>

                    <!-- CARD FIELDS -->
                    <div class="card-content">
                        <?php
                        $skip = ['id', 'firstname', 'lastname', 'email', 'password', 'picture'];
                        foreach ($row as $key => $value):
                            if (in_array($key, $skip)) continue;
                            if ($value === null || $value === '') continue;
                        ?>
                            <div class="card-row">
                                <span class="label"><?= htmlspecialchars($key) ?></span>
                                <span class="value"><?= htmlspecialchars($value) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ACTIONS -->
                    <div class="card-actions">
                        <button class="btn accept">Accept</button>
                        <button class="btn decline">Decline</button>
                    </div>

                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.getElementById('logout-btn').addEventListener('click', () => {
        fetch('/backend/routes/api.php?action=logout', {
            method: 'POST',
            credentials: 'include'
        }).then(() => {
            window.location.href = '/frontend/home/index.html';
        });
    });
</script>
</body>

</html>