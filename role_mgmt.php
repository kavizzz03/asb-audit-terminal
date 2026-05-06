<?php
session_start();
// Security Check: Only Super Admins (Role 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
if ($conn->connect_error) { die("Database link severed."); }

$feedback = ['type' => '', 'msg' => ''];

// --- CRUD OPERATIONS ---

// 1. Create / Update Role
if (isset($_POST['save_role'])) {
    $role_name = strtoupper(trim($_POST['role_name']));
    $role_id = $_POST['role_id'] ?? null;

    try {
        if (empty($role_name)) throw new Exception("Role designation is required.");

        if ($role_id) {
            // Update
            $stmt = $conn->prepare("UPDATE roles SET role_name = ? WHERE id = ?");
            $stmt->bind_param("si", $role_name, $role_id);
            $action = "updated";
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?)");
            $stmt->bind_param("s", $role_name);
            $action = "authorized";
        }

        if ($stmt->execute()) {
            $feedback = ['type' => 'success', 'msg' => "System role successfully $action."];
        }
    } catch (Exception $e) {
        $feedback = ['type' => 'error', 'msg' => $e->getMessage()];
    }
}

// 2. Delete Role
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Protection: Prevent deleting Super Admin
        if ($id == 1) throw new Exception("Critical Error: The Root Super Admin role cannot be purged.");

        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $feedback = ['type' => 'success', 'msg' => "Role privilege revoked and removed."];
        }
    } catch (Exception $e) {
        $feedback = ['type' => 'error', 'msg' => $e->getMessage()];
    }
}

$roles = $conn->query("SELECT * FROM roles ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASB | Role Management</title>
	 <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.7); }
        .sidebar { background: #0f172a; }
        .crimson-btn { background: linear-gradient(135deg, #be123c 0%, #9f1239 100%); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .crimson-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -6px rgba(190, 18, 60, 0.4); }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="sidebar w-72 min-h-screen text-slate-400 p-6 hidden lg:block sticky top-0">
        <div class="mb-10 px-4">
            <h1 class="text-xl font-black text-white italic tracking-tighter">ASB <span class="text-rose-600">GROUP</span></h1>
        </div>
        <nav class="space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition text-sm">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="category_mgmt.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition text-sm">
                <i class="fa-solid fa-folder-tree"></i> Categories
            </a>
            <a href="role_mgmt.php" class="flex items-center gap-3 p-3 bg-rose-600/10 text-rose-500 rounded-xl font-bold transition text-sm">
                <i class="fa-solid fa-user-shield"></i> System Roles
            </a>
            <div class="mt-20 px-4">
                <a href="logout.php" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-rose-500 transition">
                    <i class="fa-solid fa-power-off mr-2"></i> Close Session
                </a>
            </div>
        </nav>
    </div>

    <!-- Content -->
    <main class="flex-1 p-8 lg:p-12">
        
        <div class="flex justify-between items-end mb-10">
            <div class="animate__animated animate__fadeInLeft">
                <h2 class="text-3xl font-black text-slate-900 tracking-tight uppercase italic">Access <span class="text-rose-700">Hierarchy</span></h2>
                <p class="text-slate-400 text-[10px] font-black tracking-[0.3em] mt-1 uppercase">Define Permission Levels & Authorities</p>
            </div>
            <button onclick="openRoleModal()" class="crimson-btn text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-3">
                <i class="fa-solid fa-shield-plus"></i> Define New Role
            </button>
        </div>

        <!-- Feedback UI -->
        <?php if($feedback['msg']): ?>
            <div class="animate__animated animate__backInDown mb-8 p-5 rounded-2xl flex items-center gap-4 <?php echo $feedback['type'] == 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 'bg-rose-50 text-rose-800 border border-rose-100'; ?>">
                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $feedback['type'] == 'success' ? 'bg-emerald-500/10' : 'bg-rose-500/10'; ?>">
                    <i class="fa-solid <?php echo $feedback['type'] == 'success' ? 'fa-check' : 'fa-xmark'; ?>"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-wider"><?php echo $feedback['msg']; ?></span>
            </div>
        <?php endif; ?>

        <!-- Roles Table -->
        <div class="glass-panel rounded-[2.5rem] shadow-sm overflow-hidden animate__animated animate__fadeInUp">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-900 text-[9px] font-black text-slate-500 uppercase tracking-[0.3em]">
                        <th class="px-10 py-6">Tier</th>
                        <th class="px-10 py-6">Designation</th>
                        <th class="px-10 py-6">Status</th>
                        <th class="px-10 py-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($row = $roles->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="px-10 py-6 font-black text-rose-700 text-sm">LVL-<?php echo str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?></td>
                        <td class="px-10 py-6">
                            <span class="font-extrabold text-slate-800 uppercase tracking-tighter text-base"><?php echo $row['role_name']; ?></span>
                        </td>
                        <td class="px-10 py-6">
                            <span class="px-3 py-1 bg-slate-100 text-slate-500 text-[9px] font-black rounded-full uppercase">Active Profile</span>
                        </td>
                        <td class="px-10 py-6 text-right space-x-1">
                            <button onclick='editRole(<?php echo json_encode($row); ?>)' class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-900 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-pen-nib text-xs"></i>
                            </button>
                            <?php if($row['id'] != 1): ?>
                            <a href="" onclick="return confirm('CRITICAL: Removing a role may disconnect associated users. Proceed?')" 
                               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Role Modal -->
    <div id="roleModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-50 flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
            <div class="p-12">
                <div class="flex justify-between items-center mb-10">
                    <h3 id="modalTitle" class="text-xl font-black text-slate-900 uppercase italic">Register <span class="text-rose-700">Role</span></h3>
                    <button onclick="closeRoleModal()" class="text-slate-300 hover:text-rose-900 transition"><i class="fa-solid fa-circle-xmark text-2xl"></i></button>
                </div>

                <form action="" method="POST" class="space-y-8">
                    <input type="hidden" name="role_id" id="role_id">
                    
                    <div class="relative border-b-2 border-slate-100 focus-within:border-rose-600 transition-all duration-500">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-1">Role Title</label>
                        <input type="text" name="role_name" id="role_name" required placeholder="e.g. OPERATION_MANAGER"
                            class="w-full py-4 bg-transparent outline-none font-bold text-slate-800 placeholder:text-slate-200 placeholder:font-normal uppercase tracking-wider">
                    </div>

                    <div class="bg-rose-50 p-4 rounded-2xl">
                        <p class="text-[9px] font-bold text-rose-700 leading-relaxed uppercase">
                            <i class="fa-solid fa-circle-info mr-1"></i> New roles will require manual permission mapping via the "Assign to Roles" module.
                        </p>
                    </div>

                    <button type="submit" name="save_role" class="crimson-btn w-full py-5 rounded-[1.5rem] text-white font-black uppercase tracking-[0.2em] text-[10px]">
                        Save System Authorization
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const rModal = document.getElementById('roleModal');
        
        function openRoleModal() {
            document.getElementById('modalTitle').innerHTML = 'Register <span class="text-rose-700">Role</span>';
            document.getElementById('role_id').value = '';
            document.getElementById('role_name').value = '';
            rModal.classList.remove('hidden');
        }

        function closeRoleModal() {
            rModal.classList.add('hidden');
        }

        function editRole(data) {
            document.getElementById('modalTitle').innerHTML = 'Update <span class="text-rose-700">Privilege</span>';
            document.getElementById('role_id').value = data.id;
            document.getElementById('role_name').value = data.role_name;
            rModal.classList.remove('hidden');
        }
    </script>
</body>
</html>