<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
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

// --- PROFESSIONAL SMS TEMPLATE ---
$message_content = "OFFICIAL NOTICE: ASB Group Digital Hub has been updated. A new document titled '$title' is now available in the $cat_name category. Please log in to your executive terminal for review. - System Administrator";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASB | Executive SMS Dispatch</title>
    <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; color: #f8fafc; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .btn-shadow { box-shadow: 0 10px 15px -3px rgba(190, 18, 60, 0.4); }
        
        /* Custom SweetAlert for Dark Mode */
        .swal2-popup { border-radius: 2rem !important; background: #1e293b !important; color: #f8fafc !important; border: 1px solid rgba(190, 18, 60, 0.3) !important; }
        .swal2-title { font-weight: 800 !important; text-transform: uppercase !important; font-style: italic !important; }
        .swal2-confirm { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%) !important; border-radius: 1rem !important; padding: 12px 30px !important; font-size: 10px !important; font-weight: 800 !important; letter-spacing: 0.1em !important; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-6">

    <div class="max-w-xl w-full glass rounded-[2.5rem] p-10 border border-slate-700/50 shadow-2xl">
        <div class="text-center mb-10">
            <div class="inline-block px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-[9px] font-bold text-rose-500 uppercase tracking-[0.2em] mb-4">Secure Gateway</div>
            <h2 class="text-3xl font-black italic uppercase tracking-tighter">ASB <span class="text-rose-500">Dispatch</span></h2>
            <p class="text-slate-400 text-[10px] font-semibold uppercase tracking-widest mt-1">General Management Notification System</p>
        </div>

        <div class="bg-slate-900/60 p-6 rounded-3xl mb-8 border border-slate-800 shadow-inner">
            <div class="flex justify-between items-center mb-4">
                <p class="text-[10px] text-rose-500 font-black uppercase">Official Transmission Preview</p>
                <div class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></div>
            </div>
            <p class="text-sm leading-relaxed text-slate-300 italic font-medium">"<?php echo $message_content; ?>"</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-10">
            <div class="bg-slate-800/40 p-5 rounded-3xl border border-slate-700/30 text-center">
                <span class="block text-[9px] font-black text-slate-500 uppercase mb-1">Target Queue</span>
                <span class="text-3xl font-black text-white"><?php echo count($phone_numbers); ?></span>
            </div>
            <div class="bg-slate-800/40 p-5 rounded-3xl border border-slate-700/30 text-center">
                <span class="block text-[9px] font-black text-slate-500 uppercase mb-1">Clearance</span>
                <span class="text-xs font-black text-rose-500 uppercase"><?php echo ($branch_id == 3) ? 'Group-Wide' : 'Branch-Specific'; ?></span>
            </div>
        </div>

        <div id="statusBox" class="hidden mb-8 p-5 bg-rose-500/5 border border-rose-500/20 rounded-2xl text-center">
            <div class="flex items-center justify-center gap-3">
                <div class="w-1.5 h-1.5 bg-rose-500 rounded-full animate-bounce"></div>
                <div id="progressText" class="text-[10px] text-slate-300 font-black uppercase tracking-tighter">System Ready</div>
            </div>
        </div>

        <div class="flex gap-4">
            <button id="sendBtn" class="flex-1 crimson-gradient py-5 rounded-2xl text-white text-[10px] font-black uppercase tracking-[0.2em] transition-all hover:brightness-125 btn-shadow active:scale-95">
                Authorize & Send
            </button>
            <a href="document_mgmt.php" class="px-8 py-5 bg-slate-800/50 rounded-2xl text-slate-500 text-[10px] font-black uppercase border border-slate-700/50 hover:text-white transition-colors">Abort</a>
        </div>
    </div>

    <script>
        document.getElementById('sendBtn').onclick = async function() {
            const numbers = <?php echo json_encode($phone_numbers); ?>;
            const content = <?php echo json_encode($message_content); ?>;
            const statusBox = document.getElementById('statusBox');
            const progressText = document.getElementById('progressText');
            const btn = this;

            if(numbers.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'QUEUE EMPTY',
                    text: 'Zero recipients matched current clearance filters.',
                    confirmButtonText: 'ACKNOWLEDGE'
                });
                return;
            }

            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            statusBox.classList.remove('hidden');

            const chunkSize = 45;
            const batches = [];
            for (let i = 0; i < numbers.length; i += chunkSize) {
                batches.push(numbers.slice(i, i + chunkSize));
            }

            try {
                for (let i = 0; i < batches.length; i++) {
                    progressText.innerText = `Transmitting Batch ${i + 1} of ${batches.length}...`;

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

                progressText.innerText = "All Transmissions Successful";

                Swal.fire({
                    icon: 'success',
                    title: 'TRANSMISSION COMPLETE',
                    text: 'Notification sequence finished. Executive management has been alerted.',
                    confirmButtonText: 'RETURN TO HUB'
                }).then(() => {
                    window.location.href = "document_mgmt.php";
                });

            } catch (error) {
                console.error("Transmission Failure:", error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'CRITICAL DISPATCH ERROR',
                    text: error.message,
                    confirmButtonText: 'RETRY SYSTEM'
                });

                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                statusBox.classList.add('hidden');
            }
        };
    </script>
</body>
</html>