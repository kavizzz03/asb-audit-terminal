<?php
session_start();
// Security Check: Only Super Admins (Role 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Filter Options
$roles = $conn->query("SELECT * FROM roles");
$categories = $conn->query("SELECT * FROM categories");

// Intelligence Query
$query = "SELECT di.*, r.role_name, b.branch_name 
          FROM document_interactions di
          JOIN users u ON di.user_id = u.id
          JOIN roles r ON u.role_id = r.id
          JOIN branches b ON u.branch_id = b.id
          ORDER BY di.clicked_at DESC";

$logs = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligence Reports | ASB Group</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="css\interactions-styles.css">
</head>
<body>
    <main class="main-container">
        
        <!-- Top Navigation -->
        <div class="top-nav no-print">
            <a href="dashboard.php" class="back-link">
                <div class="back-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </div>
                <span>Return to Dashboard</span>
            </a>
            
            <div class="live-badge">
                <span class="live-dot"></span>
                <p class="live-text">Live Audit Stream Active</p>
            </div>
        </div>

        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h2 class="page-title">
                    Interaction <span class="accent-text">Intelligence</span>
                </h2>
                <p class="page-subtitle">Advanced Audit & Reporting Engine</p>
            </div>
            
            <button onclick="window.print()" class="export-btn no-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export System Log
            </button>
        </header>

        <!-- Filter Suite -->
        <div class="filter-suite no-print animate-fadeIn">
            <div class="filter-group">
                <label class="filter-label">Search Records</label>
                <input type="text" id="globalSearch" placeholder="Search anything..." class="filter-input">
            </div>

            <div class="filter-group">
                <label class="filter-label">Role Level</label>
                <select id="roleFilter" class="filter-input">
                    <option value="">All Roles</option>
                    <?php while($r = $roles->fetch_assoc()): ?>
                        <option value="<?php echo strtoupper($r['role_name']); ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Category</label>
                <select id="catFilter" class="filter-input">
                    <option value="">All Categories</option>
                    <?php while($c = $categories->fetch_assoc()): ?>
                        <option value="<?php echo strtoupper($c['category_name']); ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Action Type</label>
                <select id="actionFilter" class="filter-input">
                    <option value="">All Actions</option>
                    <option value="VIEW">View</option>
                    <option value="DOWNLOAD">Download</option>
                </select>
            </div>
        </div>

        <!-- Intelligence Table -->
        <div class="table-wrapper animate-fadeInUp">
            <table class="data-table" id="intelTable">
                <thead>
                    <tr>
                        <th>User / Branch</th>
                        <th>Document Target</th>
                        <th class="text-center">Action</th>
                        <th class="text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while($row = $logs->fetch_assoc()): ?>
                        <tr class="log-row" 
                            data-role="<?php echo strtoupper($row['role_name']); ?>" 
                            data-cat="<?php echo strtoupper($row['category_name']); ?>" 
                            data-action="<?php echo strtoupper($row['interaction_type']); ?>">
                            
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($row['user_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="user-name"><?php echo htmlspecialchars($row['user_name']); ?></p>
                                        <p class="user-meta">
                                            <?php echo htmlspecialchars($row['role_name']); ?> • <span class="branch-name"><?php echo htmlspecialchars($row['branch_name']); ?></span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <p class="doc-name">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                        <polyline points="13 2 13 9 20 9"/>
                                    </svg>
                                    <?php echo htmlspecialchars($row['doc_name']); ?>
                                </p>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td>

                            <td class="text-center">
                                <span class="action-badge <?php echo strtolower($row['interaction_type']) == 'download' ? 'action-download' : 'action-view'; ?>">
                                    <?php echo $row['interaction_type']; ?>
                                </span>
                            </td>

                            <td class="text-right">
                                <div class="timestamp">
                                    <span class="date"><?php echo date('M d, Y', strtotime($row['clicked_at'])); ?></span>
                                    <span class="time"><?php echo date('H:i A', strtotime($row['clicked_at'])); ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-content">
                                    <div class="empty-icon">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                            <line x1="8" y1="21" x2="16" y2="21"/>
                                            <line x1="12" y1="17" x2="12" y2="21"/>
                                        </svg>
                                    </div>
                                    <p class="empty-text">No Interaction Logs Found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        const inputs = [
            document.getElementById('globalSearch'),
            document.getElementById('roleFilter'),
            document.getElementById('catFilter'),
            document.getElementById('actionFilter')
        ];

        inputs.forEach(input => {
            if (input) {
                input.addEventListener('input', filterTable);
                if (input.tagName === 'SELECT') {
                    input.addEventListener('change', filterTable);
                }
            }
        });

        function filterTable() {
            const searchTerm = inputs[0] ? inputs[0].value.toUpperCase() : '';
            const roleTerm = inputs[1] ? inputs[1].value.toUpperCase() : '';
            const catTerm = inputs[2] ? inputs[2].value.toUpperCase() : '';
            const actionTerm = inputs[3] ? inputs[3].value.toUpperCase() : '';
            
            const rows = document.querySelectorAll('.log-row');

            rows.forEach(row => {
                const text = row.innerText.toUpperCase();
                const rowRole = row.getAttribute('data-role');
                const rowCat = row.getAttribute('data-cat');
                const rowAction = row.getAttribute('data-action');

                const matchesSearch = text.includes(searchTerm);
                const matchesRole = !roleTerm || rowRole === roleTerm;
                const matchesCat = !catTerm || rowCat === catTerm;
                const matchesAction = !actionTerm || rowAction === actionTerm;

                row.style.display = (matchesSearch && matchesRole && matchesCat && matchesAction) ? "" : "none";
            });
        }
    </script>
</body>
</html>