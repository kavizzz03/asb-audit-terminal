<?php
session_start();
// Security Lockdown: Super Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// Set Timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");

// 1. FILTER LOGIC
$where_clauses = [];
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$status_filter = $_GET['status'] ?? '';

if (!empty($date_from)) $where_clauses[] = "event_time >= '$date_from 00:00:00'";
if (!empty($date_to)) $where_clauses[] = "event_time <= '$date_to 23:59:59'";
if (!empty($status_filter)) $where_clauses[] = "status = '$status_filter'";

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$query = "SELECT * FROM access_audit_logs $where_sql ORDER BY event_time DESC";
$results = $conn->query($query);

$total_logs = $results->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASB Audit Terminal | Colombo HQ</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/audit-styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Header for Print -->
        <div class="print-header hidden-print">
            <div class="print-header-left">
                <h1 class="print-title">ASB <span class="accent-text">GROUP</span></h1>
                <p class="print-subtitle">Access Governance & Audit • Kaluthara, LK</p>
            </div>
            <div class="print-header-right">
                <p class="print-label">Local Dispatch Time</p>
                <p class="print-time"><?php echo date('Y-m-d | h:i A'); ?></p>
            </div>
        </div>

        <div class="main-wrapper">
            <!-- HEADER & BACK BUTTON SECTION -->
            <div class="top-section no-print">
                <div class="back-section">
                    <a href="dashboard.php" class="back-button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </a>
                    <div>
                        <h2 class="page-title">Audit <span class="accent-text">Terminal</span></h2>
                        <p class="page-subtitle sinhala-text">විගණන පර්යන්තය | Kaluthara Western Province Infrastructure</p>
                    </div>
                </div>

                <div class="stats-cards">
                    <div class="stat-card">
                        <p class="stat-label">Instance Time / වේලාව</p>
                        <p class="stat-value"><?php echo date('H:i'); ?> <span class="stat-unit">LKT</span></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">System Status / තත්වය</p>
                        <p class="stat-value secure">Secure</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Total Logs / ලොග්</p>
                        <p class="stat-value"><?php echo $total_logs; ?></p>
                    </div>
                </div>
            </div>

            <!-- TACTICAL FILTERS -->
            <div class="filters-section no-print">
                <form class="filters-form" method="GET">
                    <div class="filter-group">
                        <label class="filter-label">Start Date / ආරම්භක දිනය</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">End Date / අවසන් දිනය</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status / තත්වය</label>
                        <select name="status" class="filter-select">
                            <option value="">සියල්ල / All</option>
                            <option value="AUTHORIZED" <?php echo $status_filter == 'AUTHORIZED' ? 'selected' : ''; ?>>AUTHORIZED</option>
                            <option value="DENIED" <?php echo $status_filter == 'DENIED' ? 'selected' : ''; ?>>DENIED</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 13 10 21 14 18 14 13 22 3"/>
                            </svg>
                            Apply / යොදන්න
                        </button>
                        <button type="button" onclick="window.print()" class="btn-print">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Print Report / මුද්‍රණය කරන්න
                        </button>
                    </div>
                </form>
            </div>

            <!-- MAIN DATA TERMINAL -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entry ID / ඇතුළත් කිරීම</th>
                            <th>Operator Identity / ක්‍රියාකරු</th>
                            <th>Timestamp (LKT) / වේලාව</th>
                            <th class="text-center">Status / තත්වය</th>
                            <th>IP Origin / IP ලිපිනය</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results && $results->num_rows > 0): ?>
                            <?php while($row = $results->fetch_assoc()): ?>
                            <tr class="table-row">
                                <td class="entry-id">#<?php echo sprintf("%05d", $row['id']); ?></td>
                                <td class="operator-name"><?php echo htmlspecialchars($row['operator_name']); ?></td>
                                <td>
                                    <p class="date-display"><?php echo date('d M, Y', strtotime($row['event_time'])); ?></p>
                                    <p class="time-display"><?php echo date('h:i A', strtotime($row['event_time'])); ?></p>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge <?php echo $row['status'] == 'AUTHORIZED' ? 'status-authorized' : 'status-denied'; ?>">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <?php if($row['status'] == 'AUTHORIZED'): ?>
                                                <polyline points="20 6 9 17 4 12"/>
                                            <?php else: ?>
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            <?php endif; ?>
                                        </svg>
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="ip-address">
                                    <code><?php echo htmlspecialchars($row['ip_address'] ?: '::1'); ?></code>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div class="empty-content">
                                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 8v4M12 16h.01"/>
                                        </svg>
                                        <p class="empty-title">විගණන ලොග් නොමැත</p>
                                        <p class="empty-subtitle">No audit logs found for the selected criteria</p>
                                    </div>
                                </td>
                            </table>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- FOOTER (Print only) -->
            <div class="print-footer hidden-print">
                <div class="footer-left">
                    <p class="footer-label">Compliance Verification</p>
                    <p class="footer-text">Audit Log Integrity Secured. Location: Kaluthara (GMT+5:30).</p>
                </div>
                <div class="footer-right">
                    <p class="footer-label">Engineering Signature</p>
                    <p class="footer-signature">Vexel IT • Kavizz</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh filter if needed, maintain URL state
        document.querySelectorAll('.filter-input, .filter-select').forEach(el => {
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', () => el.closest('form').submit());
            }
        });
    </script>
</body>
</html>