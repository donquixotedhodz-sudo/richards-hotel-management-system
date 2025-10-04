<?php
$page_title = 'Account Settings';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
  <div class="content-section active">
    <div class="content-header">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2 class="mb-0"><i class="fas fa-user-cog me-2"></i>Account Settings</h2>
          <p class="text-muted mb-0">Manage your admin profile and password</p>
        </div>
      </div>
    </div>

    <div class="content-body">
      <div id="accAlert" class="alert d-none" role="alert"></div>

      <div class="row g-3">
        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center">
              <i class="fas fa-id-card me-2"></i>
              <strong>Profile</strong>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" placeholder="Admin Name">
              </div>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" id="username" placeholder="username">
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="email" placeholder="admin@example.com">
              </div>
              <button class="btn btn-primary btn-sm btn-rect d-inline-flex align-items-center" onclick="saveProfile()">
                <i class="fas fa-save me-2"></i>Save Profile
              </button>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center">
              <i class="fas fa-key me-2"></i>
              <strong>Change Password</strong>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password">
              </div>
              <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password">
              </div>
              <button class="btn btn-outline-primary btn-sm btn-rect d-inline-flex align-items-center" onclick="changePassword()">
                <i class="fas fa-shield-alt me-2"></i>Update Password
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const accCtrl = 'controller/AccountSettingsController.php';

function showAccAlert(type, msg) {
  const el = document.getElementById('accAlert');
  el.className = `alert alert-${type}`;
  el.textContent = msg;
  el.classList.remove('d-none');
  setTimeout(() => el.classList.add('d-none'), 3500);
}

async function loadProfile() {
  try {
    const res = await fetch(`${accCtrl}?action=get_profile`);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load');
    const p = data.profile;
    document.getElementById('full_name').value = p.full_name || '';
    document.getElementById('username').value = p.username || '';
    document.getElementById('email').value = p.email || '';
  } catch (e) {
    showAccAlert('danger', e.message);
  }
}

async function saveProfile() {
  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('full_name', document.getElementById('full_name').value.trim());
  fd.append('username', document.getElementById('username').value.trim());
  fd.append('email', document.getElementById('email').value.trim());
  try {
    const res = await fetch(accCtrl, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to save');
    showAccAlert('success', 'Profile updated');
  } catch (e) {
    showAccAlert('danger', e.message);
  }
}

async function changePassword() {
  const fd = new FormData();
  fd.append('action', 'change_password');
  fd.append('current_password', document.getElementById('current_password').value);
  fd.append('new_password', document.getElementById('new_password').value);
  fd.append('confirm_password', document.getElementById('confirm_password').value);
  try {
    const res = await fetch(accCtrl, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to change password');
    showAccAlert('success', 'Password updated');
    document.getElementById('current_password').value = '';
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
  } catch (e) {
    showAccAlert('danger', e.message);
  }
}

document.addEventListener('DOMContentLoaded', loadProfile);
</script>