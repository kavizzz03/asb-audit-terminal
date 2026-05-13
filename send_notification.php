<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$title = $_GET['title'] ?? 'New Document';
$cat_id = intval($_GET['cat_id'] ?? 0);
$branch_id = intval($_GET['branch_id'] ?? 0);
$cat_name = $_GET['cat_name'] ?? 'General';

// SQL Logic: Filter by Role Access and Branch
$sql = "SELECT DISTINCT u.contact_number 
        FROM users u
        INNER JOIN role_category_access rca ON u.role_id = rca.role_id
        WHERE rca.category_id = $cat_id";

if ($branch_id != 3) {
    $sql .= " AND u.branch_id = $branch_id";
}

$result = $conn->query($sql);
$phone_numbers = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['contact_number'])) {
        $phone_numbers[] = trim($row['contact_number']);
    }
}

// --- PROFESSIONAL SMS TEMPLATE (Bilingual) ---
$message_sinhala = "ASB සමූහ සමාගම් නිවේදනය: '$title' නම් නව ලේඛනය $cat_name ප්‍රවර්ගයට එක් කර ඇත. කරුණාකර ඔබේ පර්යන්තයට පිවිස පරීක්ෂා කරන්න.";
$message_english = "OFFICIAL NOTICE: ASB Group Digital Hub has been updated. A new document titled '$title' is now available in the $cat_name category. Please log in to your executive terminal for review.";
$message_content = $message_english; // Default to English for SMS API
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASB | Executive SMS Dispatch</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/send-styles.css">
</head>
<body>
    <div class="dispatch-container">
        <div class="dispatch-card">
            <div class="card-header">
                <div class="badge">Secure Gateway / ආරක්ෂිත ද්වාරය</div>
                <h1 class="card-title">ASB <span class="accent">Dispatch</span></h1>
                <p class="card-subtitle sinhala">දැනුම්දීම් පද්ධතිය | General Management Notification System</p>
            </div>

            <!-- Message Preview -->
            <div class="message-preview">
                <div class="preview-header">
                    <span class="preview-label">Official Transmission Preview / නිල පණිවිඩය</span>
                    <div class="live-dot"></div>
                </div>
                <div class="preview-content">
                    <p class="preview-english">"<?php echo htmlspecialchars($message_english); ?>"</p>
                    <p class="preview-sinhala sinhala">"<?php echo htmlspecialchars($message_sinhala); ?>"</p>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-label">Target Queue / ඉලක්කගත පිරිස</span>
                    <span class="stat-value"><?php echo count($phone_numbers); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Clearance / අවසරය</span>
                    <span class="stat-value small <?php echo ($branch_id == 3) ? 'wide' : 'branch'; ?>">
                        <?php echo ($branch_id == 3) ? 'Group-Wide / සමූහ' : 'Branch-Specific / ශාඛා'; ?>
                    </span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Category / ප්‍රවර්ගය</span>
                    <span class="stat-value small"><?php echo htmlspecialchars($cat_name); ?></span>
                </div>
            </div>

            <!-- Progress Status -->
            <div id="statusBox" class="status-box hidden">
                <div class="progress-indicator">
                    <div class="progress-dot"></div>
                    <div id="progressText" class="progress-text">System Ready / පද්ධතිය සූදානම්</div>
                </div>
                <div class="progress-bar-container">
                    <div id="progressBar" class="progress-bar" style="width: 0%"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button id="sendBtn" class="btn-send">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Authorize & Send / යවන්න
                </button>
                <a href="document_mgmt.php" class="btn-abort">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Abort / අවලංගු කරන්න
                </a>
            </div>

            <!-- Footer Note -->
            <div class="footer-note">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p>Recipients are automatically filtered based on role permissions and branch access levels</p>
            </div>
        </div>
    </div>

    <!-- Custom Alert Modal (Replaces SweetAlert2) -->
    <div id="customAlert" class="custom-alert hidden">
        <div class="custom-alert-content">
            <div id="alertIcon" class="alert-icon"></div>
            <h3 id="alertTitle" class="alert-title"></h3>
            <p id="alertMessage" class="alert-message"></p>
            <div class="alert-actions">
                <button id="alertConfirm" class="alert-btn confirm-btn">හරි / OK</button>
            </div>
        </div>
    </div>

    <script>
        // Custom Alert System
        const customAlert = document.getElementById('customAlert');
        const alertTitle = document.getElementById('alertTitle');
        const alertMessage = document.getElementById('alertMessage');
        const alertIcon = document.getElementById('alertIcon');
        const alertConfirm = document.getElementById('alertConfirm');

        function showAlert(type, title, message, onConfirm) {
            customAlert.classList.remove('hidden');
            customAlert.classList.add('flex');
            
            if (type === 'success') {
                alertIcon.innerHTML = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>`;
                alertIcon.className = 'alert-icon success-icon';
            } else if (type === 'error') {
                alertIcon.innerHTML = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>`;
                alertIcon.className = 'alert-icon error-icon';
            } else if (type === 'warning') {
                alertIcon.innerHTML = `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 8v4M12 16h.01"/><circle cx="12" cy="12" r="10"/>
                </svg>`;
                alertIcon.className = 'alert-icon warning-icon';
            }
            
            alertTitle.textContent = title;
            alertMessage.textContent = message;
            
            const closeAlert = () => {
                customAlert.classList.add('hidden');
                customAlert.classList.remove('flex');
                if (onConfirm) onConfirm();
            };
            
            alertConfirm.onclick = closeAlert;
            customAlert.onclick = (e) => {
                if (e.target === customAlert) closeAlert();
            };
        }

        // SMS Sending Logic
        document.getElementById('sendBtn').onclick = async function() {
            const numbers = <?php echo json_encode($phone_numbers); ?>;
            const content = <?php echo json_encode($message_content); ?>;
            const statusBox = document.getElementById('statusBox');
            const progressText = document.getElementById('progressText');
            const progressBar = document.getElementById('progressBar');
            const btn = this;

            if(numbers.length === 0) {
                showAlert('warning', 'QUEUE EMPTY / ඉලක්ක හිස්', 'Zero recipients matched current clearance filters. පිළිගැනීම් කිසිවක් නොමැත.');
                return;
            }

            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            statusBox.classList.remove('hidden');

            const chunkSize = 45;
            const batches = [];
            for (let i = 0; i < numbers.length; i += chunkSize) {
                batches.push(numbers.slice(i, i + chunkSize));
            }

            try {
                for (let i = 0; i < batches.length; i++) {
                    const percent = ((i + 1) / batches.length) * 100;
                    progressText.innerText = `Transmitting Batch ${i + 1} of ${batches.length}... / පණිවිඩ ${i + 1}/${batches.length} යවමින්...`;
                    progressBar.style.width = `${percent}%`;

                    const response = await fetch('proxy_sms.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            numbers: batches[i],
                            content: content
                        })
                    });

                    if (!response.ok) throw new Error(`Gateway Error: ${response.status}`);
                    const result = await response.json();
                    if(!result.success) throw new Error(result.error || "Batch rejection at API level");
                }

                progressText.innerText = "All Transmissions Successful! / සියලු පණිවිඩ යවන ලදී!";
                progressBar.style.width = '100%';

                showAlert('success', 'TRANSMISSION COMPLETE / පණිවිඩ යවන ලදී', 'Notification sequence finished. Executive management has been alerted. දැනුම්දීම් සාර්ථකව යවන ලදී.', () => {
                    window.location.href = "document_mgmt.php";
                });

            } catch (error) {
                console.error("Transmission Failure:", error);
                
                showAlert('error', 'TRANSMISSION FAILED / දෝෂයක්', error.message || 'Failed to send SMS notifications. කරුණාකර නැවත උත්සාහ කරන්න.', () => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    statusBox.classList.add('hidden');
                    progressBar.style.width = '0%';
                });
            }
        };
    </script>
</body>
</html>