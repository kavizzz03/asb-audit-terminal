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

// 1. FILTER LOGIC
$where_clauses = [];
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if (!empty($date_from)) $where_clauses[] = "event_time >= '$date_from 00:00:00'";
if (!empty($date_to)) $where_clauses[] = "event_time <= '$date_to 23:59:59'";

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$query = "SELECT * FROM access_audit_logs $where_sql ORDER BY event_time DESC";
$results = $conn->query($query);

$total_logs = $results->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASB Audit Terminal | Colombo HQ</title>
	 <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.8); }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .status-pill { font-size: 9px; font-weight: 900; letter-spacing: 0.1em; padding: 4px 12px; border-radius: 99px; }
        .btn-back { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); background: #0f172a; }
        .btn-back:hover { transform: translateX(-5px); background: #be123c; box-shadow: 0 10px 20px -5px rgba(190, 18, 60, 0.4); }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .glass-panel { border: none; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body class="p-4 md:p-10">

    <!-- REPORT HEADER (Print Only) -->
    <div class="hidden print:flex flex-col mb-10 border-b-4 border-rose-700 pb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">ASB <span class="text-rose-700">GROUP</span></h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-[0.3em]">Access Governance & Audit • Kaluthara, LK</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-black text-slate-400 uppercase">Local Dispatch Time</p>
                <p class="text-sm font-bold text-slate-800"><?php echo date('Y-m-d | h:i A'); ?></p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- HEADER & BACK BUTTON SECTION -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6 no-print animate__animated animate__fadeIn">
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="btn-back text-white h-12 w-12 rounded-2xl flex items-center justify-center shadow-lg group">
                    <i class="fa-solid fa-arrow-left text-sm group-hover:scale-110 transition-transform"></i>
                </a>
                <div>
                    <h2 class="text-3xl font-black text-slate-900 uppercase italic tracking-tighter leading-none">Audit <span class="text-rose-700">Terminal</span></h2>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-2">Kaluthara Western Province Infrastructure</p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-white px-6 py-3 rounded-2xl border border-slate-200 shadow-sm">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mb-1">Instance Time</p>
                    <p class="text-lg font-black text-slate-800 italic"><?php echo date('H:i'); ?> <span class="text-xs text-slate-400">LKT</span></p>
                </div>
                <div class="bg-white px-6 py-3 rounded-2xl border border-slate-200 shadow-sm">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mb-1">System Status</p>
                    <p class="text-lg font-black text-emerald-600 italic uppercase">Secure</p>
                </div>
            </div>
        </div>

        <!-- TACTICAL FILTERS -->
        <div class="bg-slate-900 p-1 rounded-[2.5rem] mb-8 no-print animate__animated animate__fadeInUp">
            <form class="flex flex-wrap items-center gap-4 bg-white p-6 rounded-[2.3rem] shadow-inner">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2 mb-2 block">Start Date</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:border-rose-500 outline-none">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2 mb-2 block">End Date</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:border-rose-500 outline-none">
                </div>
                <div class="flex gap-3 self-end">
                    <button type="submit" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition">Apply</button>
                    <button type="button" onclick="window.print()" class="crimson-gradient text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-rose-200">
                        <i class="fa-solid fa-print mr-2"></i> Print Report
                    </button>
                </div>
            </form>
        </div>

        <!-- MAIN DATA TERMINAL -->
        <div class="glass-panel rounded-[3rem] shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__delay-1s">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest">Entry ID</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest">Operator Identity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest">Timestamp (LKT)</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-center">Status</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest">IP Origin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($results->num_rows > 0): ?>
                        <?php while($row = $results->fetch_assoc()): ?>
                        <tr class="hover:bg-rose-50/40 transition-colors">
                            <td class="px-8 py-6 text-[10px] font-black text-slate-300">#<?php echo sprintf("%05d", $row['id']); ?></td>
                            <td class="px-8 py-6 font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($row['operator_name']); ?></td>
                            <td class="px-8 py-6">
                                <p class="text-xs font-black text-slate-700 italic"><?php echo date('d M, Y', strtotime($row['event_time'])); ?></p>
                                <p class="text-[10px] text-slate-400 font-bold"><?php echo date('h:i A', strtotime($row['event_time'])); ?></p>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="status-pill <?php echo $row['status'] == 'AUTHORIZED' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <code class="text-[10px] font-black text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg"><?php echo $row['ip_address']; ?></code>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- FOOTER (Print only) -->
        <div class="hidden print:block mt-20 border-t-2 border-slate-900 pt-10">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Compliance Verification</p>
                    <p class="text-[11px] font-bold text-slate-800 italic">Audit Log Integrity Secured. Location: Kaluthara (GMT+5:30).</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Engineering Signature</p>
                    <p class="text-[12px] font-black text-rose-700 uppercase italic">Vexel IT • Kavizz</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>