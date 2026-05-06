<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

// --- CRUD LOGIC ---

// 1. Create User
if (isset($_POST['add_user'])) {
    $uname = $_POST['username'];
    $name = $_POST['name'];
    $pass = $_POST['password']; 
    $phone = $_POST['contact_number'];
    $email = $_POST['email'];
    $role = $_POST['role_id'];
    $branch = $_POST['branch_id'];

    $stmt = $conn->prepare("INSERT INTO users (username, name, password, contact_number, email, role_id, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $uname, $name, $pass, $phone, $email, $role, $branch);
    
    if($stmt->execute()) {
        $_SESSION['msg'] = "Identity Provisioned Successfully";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Provisioning Failed";
        $_SESSION['msg_type'] = "error";
    }
}

// 2. Update User
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    if ($id != 1) {
        $uname = $_POST['username'];
        $name = $_POST['name'];
        $pass = $_POST['password'];
        $phone = $_POST['contact_number'];
        $email = $_POST['email'];
        $role = $_POST['role_id'];
        $branch = $_POST['branch_id'];

        $stmt = $conn->prepare("UPDATE users SET username=?, name=?, password=?, contact_number=?, email=?, role_id=?, branch_id=? WHERE id=?");
        $stmt->bind_param("sssssiii", $uname, $name, $pass, $phone, $email, $role, $branch, $id);
        
        if($stmt->execute()) {
            $_SESSION['msg'] = "Identity Modified Successfully";
            $_SESSION['msg_type'] = "success";
        }
    }
}

// 3. Delete User
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']); 
    if ($id != 1) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $_SESSION['msg'] = "Operator Terminated Successfully";
            $_SESSION['msg_type'] = "success";
        }
        header("Location: user_mgmt.php");
        exit(); 
    }
}

$users = $conn->query("SELECT u.*, r.role_name, b.branch_name FROM users u LEFT JOIN roles r ON u.role_id = r.id LEFT JOIN branches b ON u.branch_id = b.id ORDER BY u.id ASC");
$roles_list = $conn->query("SELECT id, role_name FROM roles");
$branches_list = $conn->query("SELECT id, branch_name FROM branches");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASB | User Control</title>
	<link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .master-lock { background: #fff1f2; border-left: 4px solid #be123c; }
        .input-field { background: #f1f5f9; border: 1px solid #e2e8f0; transition: all 0.3s; }
        .input-field:focus { border-color: #be123c; background: #fff; box-shadow: 0 0 0 4px rgba(190, 18, 60, 0.1); outline: none; }
        
        /* Custom SweetAlert Styling */
        .swal2-popup { border-radius: 2rem !important; font-family: 'Plus Jakarta Sans', sans-serif !important; }
        .swal2-confirm { background: #be123c !important; border-radius: 1rem !important; text-transform: uppercase !important; font-weight: 800 !important; font-size: 10px !important; letter-spacing: 0.1em !important; padding: 15px 30px !important; }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="w-80 min-h-screen bg-[#0f172a] p-6 text-slate-400 hidden lg:block">
        <div class="mb-12 px-4 italic font-black text-white text-xl">ASB <span class="text-rose-600">GROUP</span></div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition text-sm"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="user_mgmt.php" class="flex items-center gap-3 p-3 bg-rose-600/10 text-rose-500 rounded-xl font-bold text-sm"><i class="fa-solid fa-user-shield"></i> User Management</a>
            <a href="branch_mgmt.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition text-sm"><i class="fa-solid fa-building"></i> Branch Network</a>
        </nav>
    </div>

    <main class="flex-1 p-10 h-screen overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Operator <span class="text-rose-700">Registry</span></h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mt-1">Access Control & Identity Management</p>
            </div>
            <button onclick="openAddModal()" class="crimson-gradient px-8 py-4 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-105 transition">
                <i class="fa-solid fa-user-plus mr-2"></i> Provision New User
            </button>
        </header>

        <div class="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-slate-100 animate__animated animate__fadeIn">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">User Profile</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Contact & ID</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Authorization</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($row = $users->fetch_assoc()): ?>
                    <tr class="<?php echo ($row['id'] == 1) ? 'master-lock' : 'hover:bg-slate-50/50'; ?> transition">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-bold text-xs">
                                    <?php echo strtoupper(substr($row['username'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 uppercase italic leading-none"><?php echo $row['name']; ?></p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-1">@<?php echo $row['username']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <p class="text-xs font-bold text-slate-700 italic"><?php echo $row['email']; ?></p>
                            <p class="text-[10px] font-black text-rose-600 mt-1 tracking-tighter"><?php echo $row['contact_number']; ?></p>
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1 bg-slate-100 rounded-lg text-[9px] font-black text-slate-600 uppercase"><?php echo $row['role_name']; ?></span>
                            <span class="ml-1 px-3 py-1 bg-rose-50 rounded-lg text-[9px] font-black text-rose-700 uppercase"><?php echo $row['branch_name']; ?></span>
                        </td>
                        <td class="px-8 py-6 text-right space-x-2">
                            <?php if($row['id'] != 1): ?>
                                <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="text-slate-400 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-pen-nib"></i>
                                </button>
                                <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>')" class="text-slate-400 hover:text-rose-600 transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            <?php else: ?>
                                <i class="fa-solid fa-shield-halved text-rose-200" title="System Master Locked"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- MODAL -->
    <div id="userModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-2xl rounded-[3rem] p-12 shadow-2xl animate__animated animate__zoomIn">
            <h3 id="modalTitle" class="text-2xl font-black text-slate-900 italic uppercase mb-8">System <span class="text-rose-700">Provisioning</span></h3>
            <form method="POST" class="grid grid-cols-2 gap-6">
                <input type="hidden" name="user_id" id="u_id">
                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Username</label>
                        <input type="text" name="username" id="u_username" required class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold">
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Full Name</label>
                        <input type="text" name="name" id="u_name" required class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold uppercase">
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Access Password</label>
                        <input type="text" name="password" id="u_pass" required class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold">
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Contact</label>
                        <input type="text" name="contact_number" id="u_phone" pattern="947[0-9]{8}" placeholder="94712345678" required class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold">
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Role Assignment</label>
                        <select name="role_id" id="u_role" class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold">
                            <?php $roles_list->data_seek(0); while($r = $roles_list->fetch_assoc()): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo $r['role_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Branch Location</label>
                        <select name="branch_id" id="u_branch" class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold">
                            <?php $branches_list->data_seek(0); while($b = $branches_list->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo $b['branch_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="col-span-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Email Address</label>
                    <input type="email" name="email" id="u_email" required class="w-full px-5 py-4 rounded-2xl input-field text-sm font-bold">
                </div>

                <div class="col-span-2 flex gap-4 pt-6">
                    <button type="submit" id="submitBtn" name="add_user" class="flex-1 crimson-gradient py-5 rounded-[1.5rem] text-white text-[10px] font-black uppercase tracking-widest">Execute Provisioning</button>
                    <button type="button" onclick="closeModal()" class="px-10 py-5 rounded-[1.5rem] bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-widest">Abort</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Handlers
        const modal = document.getElementById('userModal');
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = 'System <span class="text-rose-700">Provisioning</span>';
            document.getElementById('submitBtn').name = 'add_user';
            document.getElementById('u_id').value = '';
            document.querySelector('form').reset();
            modal.classList.remove('hidden');
        }

        function openEditModal(user) {
            document.getElementById('modalTitle').innerHTML = 'Modify <span class="text-rose-700">Identity</span>';
            document.getElementById('submitBtn').name = 'update_user';
            document.getElementById('u_id').value = user.id;
            document.getElementById('u_username').value = user.username;
            document.getElementById('u_name').value = user.name;
            document.getElementById('u_pass').value = user.password;
            document.getElementById('u_phone').value = user.contact_number;
            document.getElementById('u_email').value = user.email;
            document.getElementById('u_role').value = user.role_id;
            document.getElementById('u_branch').value = user.branch_id;
            modal.classList.remove('hidden');
        }

        function closeModal() { modal.classList.add('hidden'); }

        // SweetAlert Delete Confirmation
        function confirmDelete(id, name) {
            Swal.fire({
                title: 'TERMINATE OPERATOR?',
                text: `Immediate deletion of: ${name}. This action is irreversible.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'CONFIRM TERMINATION',
                cancelButtonText: 'ABORT',
                background: '#fff',
                color: '#0f172a',
                iconColor: '#be123c',
                customClass: {
                    confirmButton: 'swal2-confirm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `user_mgmt.php?delete=${id}`;
                }
            });
        }

        // Trigger Flash Messages
        <?php if(isset($_SESSION['msg'])): ?>
            Swal.fire({
                title: 'SYSTEM NOTIFICATION',
                text: '<?php echo $_SESSION['msg']; ?>',
                icon: '<?php echo $_SESSION['msg_type']; ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
        <?php endif; ?>
    </script>
</body>
</html>