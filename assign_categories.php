<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

// Status handling for SweetAlert
$status = $_GET['status'] ?? '';

// 1. Grant Access
if (isset($_POST['grant_access'])) {
    $r_id = $_POST['role_id'];
    $c_id = $_POST['category_id'];

    $check = $conn->prepare("SELECT * FROM role_category_access WHERE role_id = ? AND category_id = ?");
    $check->bind_param("ii", $r_id, $c_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        header("Location: assign_categories.php?status=exists");
    } else {
        $stmt = $conn->prepare("INSERT INTO role_category_access (role_id, category_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $r_id, $c_id);
        $stmt->execute();
        header("Location: assign_categories.php?status=success");
    }
    exit();
}

// 2. Revoke Access (Delete)
if (isset($_GET['revoke_role']) && isset($_GET['revoke_cat'])) {
    $r_id = (int)$_GET['revoke_role'];
    $c_id = (int)$_GET['revoke_cat'];
    $conn->query("DELETE FROM role_category_access WHERE role_id = $r_id AND category_id = $c_id");
    // Redirect ensures the POST/GET data is cleared and UI refreshes
    header("Location: assign_categories.php?status=deleted");
    exit();
}

// Data Fetching
$permissions = $conn->query("SELECT rca.*, r.role_name, c.category_name 
                             FROM role_category_access rca
                             JOIN roles r ON rca.role_id = r.id
                             JOIN categories c ON rca.category_id = c.id
                             ORDER BY r.role_name ASC");

$all_roles = $conn->query("SELECT id, role_name FROM roles");
$all_cats = $conn->query("SELECT id, category_name FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Matrix | ASB Group</title>
    <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        .custom-loader { border-top-color: #be123c; animation: spinner 1.5s linear infinite; }
        @keyframes spinner { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="w-80 min-h-screen bg-[#0f172a] p-6 text-slate-400 hidden lg:block shadow-2xl">
        <div class="mb-12 px-4 italic font-black text-white text-xl tracking-widest">ASB <span class="text-rose-600">GROUP</span></div>
        <nav class="space-y-2 text-sm font-bold">
            <a href="dashboard.php" class="flex items-center gap-3 p-4 hover:bg-white/5 rounded-2xl transition"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="assign_categories.php" class="flex items-center gap-3 p-4 bg-rose-600/10 text-rose-500 rounded-2xl border border-rose-600/20"><i class="fa-solid fa-key"></i> Access Matrix</a>
            <a href="document_mgmt.php" class="flex items-center gap-3 p-4 hover:bg-white/5 rounded-2xl transition"><i class="fa-solid fa-file-shield"></i> Documents</a>
        </nav>
    </div>

    <main class="flex-1 p-10 h-screen overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Access <span class="text-rose-700">Matrix</span></h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mt-1 italic">Security Authorization Registry</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- FORM SECTION -->
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 animate__animated animate__fadeInLeft">
                    <h3 class="text-xs font-black text-slate-800 uppercase italic mb-8 flex items-center gap-2">
                        <span class="w-2 h-6 bg-rose-600 rounded-full"></span> New Permission
                    </h3>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-2">Target Role</label>
                            <select name="role_id" required class="w-full bg-slate-50 p-4 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-bold text-xs uppercase transition-all">
                                <option value="">Chose Role...</option>
                                <?php $all_roles->data_seek(0); while($r = $all_roles->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo $r['role_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-2">Category Authority</label>
                            <select name="category_id" required class="w-full bg-slate-50 p-4 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-bold text-xs uppercase transition-all">
                                <option value="">Chose Category...</option>
                                <?php $all_cats->data_seek(0); while($c = $all_cats->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['category_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" name="grant_access" class="w-full crimson-gradient py-5 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest hover:scale-[1.02] active:scale-95 transition shadow-lg shadow-rose-200">
                            Bind Access Rights
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2.5rem] overflow-hidden shadow-xl border border-slate-100 animate__animated animate__fadeIn">
                    
                    <!-- Advanced Filters -->
                    <div class="p-8 bg-slate-50/50 border-b border-slate-100 flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-[200px] relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                            <input type="text" id="matrixSearch" placeholder="Quick Search..." class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-[11px] font-bold outline-none focus:border-rose-500 transition">
                        </div>
                        
                        <select id="roleFilter" class="bg-white px-4 py-3 border border-slate-200 rounded-xl text-[10px] font-black uppercase outline-none focus:border-rose-500">
                            <option value="">All Roles</option>
                            <?php $all_roles->data_seek(0); while($r = $all_roles->fetch_assoc()): ?>
                                <option value="<?php echo strtoupper($r['role_name']); ?>"><?php echo $r['role_name']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <select id="catFilter" class="bg-white px-4 py-3 border border-slate-200 rounded-xl text-[10px] font-black uppercase outline-none focus:border-rose-500">
                            <option value="">All Categories</option>
                            <?php $all_cats->data_seek(0); while($c = $all_cats->fetch_assoc()): ?>
                                <option value="<?php echo strtoupper($c['category_name']); ?>"><?php echo $c['category_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <table class="w-full text-left" id="matrixTable">
                        <thead class="bg-slate-900 text-white">
                            <tr>
                                <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest">Role Identification</th>
                                <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest">Permission Scope</th>
                                <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest text-right">Revoke</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($row = $permissions->fetch_assoc()): ?>
                            <tr class="hover:bg-rose-50/30 transition group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-1.5 h-1.5 rounded-full bg-rose-600 group-hover:scale-150 transition"></div>
                                        <span class="text-xs font-black text-slate-700 uppercase italic tracking-tight"><?php echo $row['role_name']; ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-4 py-1.5 bg-slate-100 text-slate-600 text-[9px] font-black rounded-lg uppercase tracking-wider group-hover:bg-slate-900 group-hover:text-white transition-all">
                                        <i class="fa-solid fa-folder-tree mr-2 text-rose-500"></i><?php echo $row['category_name']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button onclick="confirmRevoke(<?php echo $row['role_id']; ?>, <?php echo $row['category_id']; ?>, '<?php echo $row['role_name']; ?>', '<?php echo $row['category_name']; ?>')" 
                                            class="w-10 h-10 rounded-full hover:bg-rose-100 text-slate-200 hover:text-rose-600 transition flex items-center justify-center ml-auto">
                                        <i class="fa-solid fa-link-slash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Dual Filter Logic
        function performFilter() {
            let search = document.getElementById('matrixSearch').value.toUpperCase();
            let roleF = document.getElementById('roleFilter').value;
            let catF = document.getElementById('catFilter').value;
            let rows = document.querySelector("#matrixTable tbody").rows;

            for (let i = 0; i < rows.length; i++) {
                let roleText = rows[i].cells[0].textContent.toUpperCase();
                let catText = rows[i].cells[1].textContent.toUpperCase();
                let fullText = rows[i].innerText.toUpperCase();

                let matchesSearch = fullText.includes(search);
                let matchesRole = roleF === "" || roleText.includes(roleF);
                let matchesCat = catF === "" || catText.includes(catF);

                rows[i].style.display = (matchesSearch && matchesRole && matchesCat) ? "" : "none";
            }
        }

        document.getElementById('matrixSearch').addEventListener('keyup', performFilter);
        document.getElementById('roleFilter').addEventListener('change', performFilter);
        document.getElementById('catFilter').addEventListener('change', performFilter);

        // SweetAlert2 Handling
        function confirmRevoke(rId, cId, rName, cName) {
            Swal.fire({
                title: 'REVOKE ACCESS?',
                html: `Removing <b class="text-rose-600">${cName}</b> access from <b class="text-slate-800">${rName}</b>.<br><small class="text-slate-400">This action is logged and immediate.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#be123c',
                cancelButtonColor: '#0f172a',
                confirmButtonText: 'YES, REVOKE',
                cancelButtonText: 'KEEP IT',
                background: '#ffffff',
                customClass: {
                    title: 'text-sm font-black italic uppercase tracking-tighter',
                    confirmButton: 'text-[10px] font-black px-6 py-3 rounded-xl uppercase',
                    cancelButton: 'text-[10px] font-black px-6 py-3 rounded-xl uppercase'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `assign_categories.php?revoke_role=${rId}&revoke_cat=${cId}`;
                }
            })
        }

        // Notification Alerts on Page Load
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status) {
            let config = {
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            };

            if (status === 'success') {
                Swal.fire({...config, icon: 'success', title: 'PERMISSION BOUND'});
            } else if (status === 'deleted') {
                Swal.fire({...config, icon: 'info', title: 'ACCESS REVOKED'});
            } else if (status === 'exists') {
                Swal.fire({...config, icon: 'error', title: 'DUPLICATE ENTRY DETECTED'});
            }
            // Clean URL after showing alert
            window.history.replaceState({}, document.title, "assign_categories.php");
        }
    </script>
</body>
</html>