<?php
use function StaffHub\Support\e;
use function StaffHub\Support\money;

$employee = $employee;
$employeeId = $employeeId;
$successMsg = $successMsg ?? '';
$errorMsg = $errorMsg ?? '';
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Employee Portal</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">My Profile</h4>
    <p class="sh-muted small mb-0 mt-1">View your employment details and manage your account.</p>
  </div>
</div>

<?php if ($successMsg): ?><div class="alert alert-success py-2 small"><i class="fa-solid fa-circle-check me-1"></i><?= e($successMsg) ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-danger py-2 small"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($errorMsg) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="sh-card p-4 text-center">
      <div id="avatarUploader" class="sh-avatar-uploader" tabindex="0" role="button" aria-label="Change profile photo" onclick="document.getElementById('pictureInput').click()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();document.getElementById('pictureInput').click();}">
        <div class="sh-avatar-uploader-img" id="profilePicturePreviewWrapper">
          <?php if (!empty($employee['profile_picture'])): ?>
            <img src="<?= UPLOAD_URL . e($employee['profile_picture']) ?>" id="profileImgPreview">
          <?php else: ?>
            <span id="profileInitials"><?= e(strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1))) ?></span>
          <?php endif; ?>
        </div>
        <div class="sh-avatar-uploader-overlay">
          <i class="fa-solid fa-camera"></i>
          <span>Change</span>
        </div>
        <div class="sh-avatar-uploader-badge"><i class="fa-solid fa-camera"></i></div>
      </div>
      <h5 class="sh-display mb-0"><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></h5>
      <p class="sh-muted small mb-1"><?= e($employee['position_name'] ?? 'Employee') ?></p>

      <form id="pictureForm" enctype="multipart/form-data">
        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
        <input type="file" name="profile_picture" id="pictureInput" accept="image/png,image/jpeg,image/webp" class="d-none">
      </form>
      <div class="sh-avatar-upload-hint">JPG, PNG or WEBP · up to 2MB</div>
      <div id="avatarUploadStatus" class="sh-avatar-upload-status"></div>

      <hr>
      <div class="text-start small">
        <div class="d-flex justify-content-between py-1"><span class="sh-muted">Employee Code</span><span class="fw-semibold"><?= e($employee['employee_code']) ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="sh-muted">Username</span><span class="fw-semibold"><?= e($employee['username']) ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="sh-muted">Department</span><span class="fw-semibold"><?= e($employee['department_name'] ?? '—') ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="sh-muted">Employment Status</span><span class="fw-semibold"><?= e($employee['employment_status']) ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="sh-muted">Date Hired</span><span class="fw-semibold"><?= date('M d, Y', strtotime($employee['date_hired'])) ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="sh-muted">Hourly Rate</span><span class="fw-semibold"><?= money((float)$employee['basic_hourly_rate']) ?></span></div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="sh-card p-4 mb-3">
      <div class="sh-section-title">Contact Information</div>
      <form method="POST" action="<?= e(rtrim(defined('APP_URL') ? APP_URL : '', '/') . '/employee/profile') ?>">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">First Name</label><input class="form-control" value="<?= e($employee['first_name']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Last Name</label><input class="form-control" value="<?= e($employee['last_name']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Email Address *</label><input type="email" name="email" class="form-control" value="<?= e($employee['email']) ?>" required></div>
          <div class="col-md-6"><label class="form-label">Contact Number *</label><input name="contact_number" class="form-control" value="<?= e($employee['contact_number']) ?>" required></div>
          <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($employee['address']) ?>"></div>
        </div>
        <div class="mt-3"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Save Changes</button></div>
        <p class="sh-muted small mt-2 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Name, department, position, and salary rate can only be changed by an administrator.</p>
      </form>
    </div>

    <div class="sh-card p-4">
      <div class="sh-section-title">Change Password</div>
      <form method="POST" action="<?= e(rtrim(defined('APP_URL') ? APP_URL : '', '/') . '/employee/profile/password') ?>">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Current Password *</label><input type="password" name="current_password" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label">New Password *</label><input type="password" name="new_password" class="form-control" required></div>
          <div class="col-md-4"><label class="form-label">Confirm Password *</label><input type="password" name="confirm_password" class="form-control" required></div>
        </div>
        <div class="form-text mb-2">Min 8 characters, at least one letter and one number.</div>
        <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-key me-1"></i>Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php $extraScripts = '<script src="' . $assetJs . '/profile.js"></script>'; ?>
