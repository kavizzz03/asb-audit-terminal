<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

$feedback = ['type' => '', 'msg' => ''];

// --- CRUD LOGIC ---

// 1. Create Branch
if (isset($_POST['add_branch'])) {
    $name = $_POST['branch_name'];
    $code = $_POST['branch_code'];
    $stmt = $conn->prepare("INSERT INTO branches (branch_name, branch_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $code);
    if($stmt->execute()) {
        $feedback = ['type' => 'success', 'msg' => "Branch successfully registered."];
    }
}

// 2. Update Branch (Protecting ID 3)
if (isset($_POST['update_branch'])) {
    $id = $_POST['branch_id'];
    if ($id != 3) {
        $name = $_POST['branch_name'];
        $code = $_POST['branch_code'];
        $stmt = $conn->prepare("UPDATE branches SET branch_name = ?, branch_code = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $code, $id);
        if($stmt->execute()) {
            $feedback = ['type' => 'success', 'msg' => "Branch infrastructure updated."];
        }
    }
}

// 3. Delete Branch Logic (Triggered by GET)
if (isset($_GET['delete_id']) && $_GET['delete_id'] != 3) {
    $id = intval($_GET['delete_id']);
    if($conn->query("DELETE FROM branches WHERE id = $id")) {
        header("Location: branch_mgmt.php?status=purged");
        exit();
    }
}

// Check for purge status after redirect
if(isset($_GET['status']) && $_GET['status'] == 'purged') {
    $feedback = ['type' => 'success', 'msg' => "Branch has been successfully purged from the network."];
}

$branches = $conn->query("SELECT * FROM branches ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASB | Branch Control</title>
    <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8); }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #9f1239 100%); }
        .restricted-row { background: #fff1f2; border-left: 4px solid #be123c; }
        .swal2-popup { border-radius: 2rem !important; font-family: 'Plus Jakarta Sans', sans-serif !important; }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="w-80 min-h-screen bg-[#0f172a] p-6 text-slate-400 hidden lg:block">
        <div class="mb-12 px-4">
            <h1 class="text-xl font-black text-white italic">ASB <span class="text-rose-600">GROUP</span></h1>
        </div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition text-sm">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="branch_mgmt.php" class="flex items-center gap-3 p-3 bg-rose-600/10 text-rose-500 rounded-xl font-bold text-sm">
                <i class="fa-solid fa-building-shield"></i> Branch Management
            </a>
        </nav>
    </div>

    <main class="flex-1 p-8 lg:p-12 h-screen overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight italic uppercase">Branch <span class="text-rose-700">Network</span></h2>
                <p class="text-slate-400 text-[10px] font-black tracking-widest uppercase mt-1">Infrastructure Logic & Terminal Control</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="crimson-gradient px-6 py-3 rounded-2xl text-white text-xs font-black uppercase tracking-widest shadow-lg shadow-rose-200 hover:scale-105 transition">
                <i class="fa-solid fa-plus mr-2"></i> Register New Branch
            </button>
        </header>

        <!-- Feedback Alert -->
        <?php if($feedback['msg']): ?>
            <div class="animate__animated animate__fadeInDown mb-6 p-4 rounded-2xl <?php echo $feedback['type'] == 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100'; ?> text-[10px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-circle-info mr-2"></i> <?php echo $feedback['msg']; ?>
            </div>
        <?php endif; ?>

        <!-- Branch Table -->
        <div class="glass-panel rounded-[2.5rem] overflow-hidden shadow-sm animate__animated animate__fadeIn">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Branch Name</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Branch Code</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($row = $branches->fetch_assoc()): ?>
                    <tr class="<?php echo ($row['id'] == 3) ? 'restricted-row' : 'hover:bg-slate-50/50'; ?> transition">
                        <td class="px-8 py-6 text-sm font-bold text-slate-400">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td class="px-8 py-6">
                            <span class="text-sm font-black text-slate-800 uppercase italic"><?php echo $row['branch_name']; ?></span>
                            <?php if($row['id'] == 3): ?>
                                <span class="ml-2 px-2 py-0.5 bg-rose-100 text-rose-700 text-[8px] font-black rounded uppercase">System Protected</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-6">
                            <code class="bg-slate-100 px-3 py-1 rounded-lg text-rose-600 font-bold text-xs"><?php echo $row['branch_code']; ?></code>
                        </td>
                        <td class="px-8 py-6 text-right space-x-2">
                            <?php if($row['id'] != 3): ?>
                                <button onclick="editBranch(<?php echo $row['id']; ?>, '<?php echo $row['branch_name']; ?>', '<?php echo $row['branch_code']; ?>')" class="p-2 text-slate-400 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <!-- Fixed Delete Button -->
                                <button onclick="confirmPurge(<?php echo $row['id']; ?>, '<?php echo $row['branch_name']; ?>')" class="p-2 text-slate-400 hover:text-rose-600 transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <i class="fa-solid fa-lock text-slate-300 p-2" title="Manual Override Disabled"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- ADD MODAL (Remains the same) -->
    <div id="addModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-6">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl animate__animated animate__zoomIn">
            <h3 class="text-2xl font-black text-slate-900 italic uppercase mb-6">New <span class="text-rose-700">Registry</span></h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Branch Designation</label>
                    <input type="text" name="branch_name" required class="w-full bg-slate-50 border border-slate-100 px-5 py-4 rounded-2xl focus:ring-2 focus:ring-rose-500 outline-none transition font-bold uppercase text-sm">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Branch Code (Terminal ID)</label>
                    <input type="text" name="branch_code" required class="w-full bg-slate-50 border border-slate-100 px-5 py-4 rounded-2xl focus:ring-2 focus:ring-rose-500 outline-none transition font-bold text-sm">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="submit" name="add_branch" class="flex-1 crimson-gradient py-4 rounded-2xl text-white text-xs font-black uppercase tracking-widest">Authorize Branch</button>
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-6 py-4 rounded-2xl bg-slate-100 text-slate-500 text-xs font-black uppercase">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL (Remains the same) -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-6">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-10 shadow-2xl">
            <h3 class="text-2xl font-black text-slate-900 italic uppercase mb-6">Modify <span class="text-rose-700">Branch</span></h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="branch_id" id="edit_id">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Designation</label>
                    <input type="text" name="branch_name" id="edit_name" required class="w-full bg-slate-50 border border-slate-100 px-5 py-4 rounded-2xl outline-none font-bold uppercase text-sm">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Code</label>
                    <input type="text" name="branch_code" id="edit_code" required class="w-full bg-slate-50 border border-slate-100 px-5 py-4 rounded-2xl outline-none font-bold text-sm">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="submit" name="update_branch" class="flex-1 crimson-gradient py-4 rounded-2xl text-white text-xs font-black uppercase tracking-widest">Commit Changes</button>
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-6 py-4 rounded-2xl bg-slate-100 text-slate-500 text-xs font-black uppercase tracking-widest">Abort</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editBranch(id, name, code) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_code').value = code;
            document.getElementById('editModal').classList.remove('hidden');
        }

        // --- SweetAlert2 Delete Function ---
        function confirmPurge(id, name) {
            Swal.fire({
                title: 'PURGE BRANCH?',
                text: `You are about to disconnect ${name} from the terminal network.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#be123c',
                cancelButtonColor: '#0f172a',
                confirmButtonText: 'YES, PURGE DATA',
                cancelButtonText: 'ABORT',
                background: '#ffffff',
                color: '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "branch_mgmt.php?delete_id=" + id;
                }
            })
        }
    </script>
</body>
</html>