<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$displayName = $_SESSION['user_name'] ?? 'User';
$initials = '';
foreach (preg_split('/\s+/', trim($displayName)) as $part) {
    if ($part !== '') { $initials .= strtoupper(mb_substr($part, 0, 1)); }
}
$initials = mb_substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Technician Portal</title>
    <link rel="icon" href="../assets/logo/bsutneu.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-color:#dc3545; --secondary-color:#343a40; --gray-color:#6c757d; --blue-color:#007bff; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color:#f8f9fa; }
        .header-nav { background: linear-gradient(90deg, #dc3545 0%, #343a40 100%); padding:10px 0; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
        .header-container { display:flex; justify-content:space-between; align-items:center; max-width:100%; margin:0 auto; padding:0 20px; }
        .header-brand { color:white; font-size:1.2rem; font-weight:600; text-decoration:none; }
        .header-brand i { margin-right:8px; }
        .header-user { color:white; font-size:0.9rem; display:flex; align-items:center; position:relative; }
        .profile-dropdown { position:relative; display:inline-block; }
        .profile-trigger { display:flex; align-items:center; gap:10px; cursor:pointer; padding:6px 8px; border-radius:9999px; transition:background-color .2s; }
        .profile-trigger:hover { background-color:rgba(255,255,255,0.08); }
        .avatar-initials { width:32px; height:32px; border-radius:50%; background:#6c757d; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; letter-spacing:.5px; }
        .user-name { color:#e5e7eb; font-weight:600; }
        .dropdown-menu { position:absolute; top:100%; right:0; background:white; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.12); min-width:280px; z-index:1000; opacity:0; visibility:hidden; transform:translateY(-10px); transition:all .3s cubic-bezier(0.4,0,0.2,1); border:1px solid rgba(0,0,0,0.08); }
        .dropdown-menu.show { opacity:1; visibility:visible; transform:translateY(0); }
        .dropdown-header { padding:20px; border-bottom:1px solid #e9ecef; display:flex; align-items:center; gap:12px; }
        .dropdown-avatar { width:60px; height:60px; border-radius:50%; background:#6c757d; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:20px; }
        .dropdown-user-info h6 { margin:0; font-size:1.1rem; font-weight:600; color:#333; }
        .dropdown-user-info p { margin:5px 0 0 0; font-size:0.9rem; color:#666; }
        .dropdown-item { display:flex; align-items:center; padding:15px 20px; text-decoration:none; color:#333; transition:background-color .2s ease; border-bottom:1px solid #f8f9fa; }
        .dropdown-item:last-child { border-bottom:none; }
        .dropdown-item:hover { background-color:#f8f9fa; text-decoration:none; color:#333; }
        .dropdown-item i { width:20px; margin-right:15px; color:#666; font-size:1.1rem; }
        .dropdown-item span { flex:1; font-weight:500; }
        .dropdown-item.logout { color:#dc3545; }
        .dropdown-item.logout:hover { background-color:#fff5f5; color:#dc3545; }
        .dropdown-item.logout i { color:#dc3545; }
        .main-content { padding:20px; margin-bottom:80px; }
        @media (min-width:768px){ .main-content{ margin-bottom:20px; } }
        .card { border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,0.08); border:none; }
        .btn-primary { background-color:var(--primary-color); border-color:var(--primary-color); }
        .btn-primary:hover { background-color:#c82333; border-color:#c82333; }
        .footer-nav { position:fixed; bottom:0; left:0; right:0; background:linear-gradient(180deg,#ffffff 0%,#f8f9fa 100%); border-top:2px solid #e9ecef; z-index:1000; padding:12px 0 8px 0; box-shadow:0 -4px 20px rgba(0,0,0,0.08); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); }
        .nav-container { display:flex; justify-content:space-around; align-items:center; max-width:100%; margin:0 auto; padding:0 10px; }
        .nav-item { display:flex; flex-direction:column; align-items:center; text-decoration:none; color:#6c757d; font-size:.75rem; font-weight:500; padding:8px 12px; border-radius:12px; transition:all .3s cubic-bezier(0.4,0,0.2,1); min-width:64px; position:relative; overflow:hidden; }
        .nav-item::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(220,53,69,.1) 0%, rgba(220,53,69,.05) 100%); border-radius:12px; opacity:0; transition:opacity .3s; }
        .nav-item i { font-size:1.3rem; margin-bottom:4px; transition:all .3s cubic-bezier(0.4,0,0.2,1); position:relative; z-index:2; }
        .nav-item span { font-size:.7rem; line-height:1.2; font-weight:600; position:relative; z-index:2; transition:all .3s ease; }
        .nav-item:hover { color:#dc3545; transform:translateY(-2px); }
        .nav-item:hover::before { opacity:1; }
        .nav-item:hover i { transform:scale(1.1); color:#dc3545; }
        .nav-item.active { color:#dc3545; background:linear-gradient(135deg, rgba(220,53,69,.15) 0%, rgba(220,53,69,.08) 100%); box-shadow:0 2px 8px rgba(220,53,69,.2); }
        .nav-item.active::before { opacity:1; }
        .nav-item.active i { transform:scale(1.15); color:#dc3545; }
        .nav-item.active span { color:#dc3545; font-weight:700; }
        .nav-item.active::after { content:''; position:absolute; bottom:-8px; left:50%; transform:translateX(-50%); width:4px; height:4px; background:#dc3545; border-radius:50%; box-shadow:0 0 8px rgba(220,53,69,.5); }
        .nav-item:active { transform:scale(0.95); }
    </style>
</head>
<body>
    <nav class="header-nav">
        <div class="header-container">
            <a href="indet.php" class="header-brand">
                <img src="User icon.png" alt="Technician Portal Logo" style="width: 40px; height: 40px; margin-right: 12px;">
                Technician Portal
            </a>
            <div class="header-user">
                <div class="profile-dropdown">
                    <div class="profile-trigger" onclick="toggleDropdown()">
                        <div class="avatar-initials"><?php echo htmlspecialchars($initials); ?></div>
                        <span class="user-name"><?php echo htmlspecialchars($displayName); ?></span>
                        <i class="fas fa-chevron-down" style="color:#9ca3af; font-size:.8rem;"></i>
                    </div>
                    <div class="dropdown-menu" id="profileDropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-avatar"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="dropdown-user-info">
                                <h6><?php echo htmlspecialchars($displayName); ?></h6>
                                <p><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            </div>
                        </div>
                        <a href="#" class="dropdown-item" onclick="openEditProfile()">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <a href="#" class="dropdown-item" onclick="openSettings()">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <a href="logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Log Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content">

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user"></i> Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editProfileForm" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="personal-info-section mb-4">
                        <h6 class="section-title">Personal Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" placeholder="username@g.batstate-u.edu.ph" required>
                                <div class="form-text">Must be from @g.batstate-u.edu.ph</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone_number" placeholder="09123456789" maxlength="11" required>
                                <div class="form-text">Must be exactly 11 digits starting with 09</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($_SESSION['user_role'] ?? 'Technician'); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-lock"></i> Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="changePasswordForm" action="profile.php">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                            <div class="form-text">Must contain uppercase, number, and special character</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirm Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sign-out-alt"></i> Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to log out?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger confirm-logout">Logout</button>
            </div>
        </div>
    </div>
</div>
    <script>
    function toggleDropdown(){ document.getElementById('profileDropdown').classList.toggle('show'); }
    function openEditProfile(){ document.getElementById('profileDropdown').classList.remove('show'); new bootstrap.Modal(document.getElementById('editProfileModal')).show(); }
    function openSettings(){ document.getElementById('profileDropdown').classList.remove('show'); new bootstrap.Modal(document.getElementById('changePasswordModal')).show(); }
    document.addEventListener('click', function(e){ const d=document.getElementById('profileDropdown'); const t=document.querySelector('.profile-trigger'); if(!t.contains(e.target)&&!d.contains(e.target)){ d.classList.remove('show'); }});
    document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ document.getElementById('profileDropdown').classList.remove('show'); }});
    document.addEventListener('DOMContentLoaded', function(){
        document.addEventListener('click', function(e){ const link=e.target.closest('a[href="logout.php"]'); if(!link) return; e.preventDefault(); const m=document.getElementById('logoutConfirmModal'); if(!m||typeof bootstrap==='undefined'||!bootstrap.Modal){ window.location.href=link.href; return;} const btn=m.querySelector('.confirm-logout'); if(btn){ btn.onclick=function(){ window.location.href=link.href; }; } new bootstrap.Modal(m).show(); });
        const emailInput=document.querySelector('input[name="email"]'); if(emailInput){ emailInput.addEventListener('input', function(){ const ok=this.value.endsWith('@g.batstate-u.edu.ph'); this.setCustomValidity(ok?'':'Email must be from @g.batstate-u.edu.ph'); }); }
        const phoneInput=document.querySelector('input[name="phone_number"]'); if(phoneInput){ phoneInput.addEventListener('input', function(){ const ok=/^09\d{9}$/.test(this.value); this.setCustomValidity(ok?'':'Phone number must be exactly 11 digits starting with 09'); }); }
        const cpForm=document.getElementById('changePasswordForm'); if(cpForm){ const np=cpForm.querySelector('input[name="new_password"]'); const cp=cpForm.querySelector('input[name="confirm_password"]'); if(np){ np.addEventListener('input', function(){ const v=this.value; const ok=v.length>=6 && /[A-Z]/.test(v) && /\d/.test(v) && /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v); this.setCustomValidity(ok?'':'Password must contain at least one uppercase letter, one number, and one special character'); }); } if(cp){ cp.addEventListener('input', function(){ this.setCustomValidity(this.value===np.value?'':'Passwords do not match'); }); } }
    });
    </script> 