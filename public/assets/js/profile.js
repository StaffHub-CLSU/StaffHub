/**
 * profile.js
 * Handles the profile-picture upload flow on employee/profile.php.
 * - Clicking the avatar (or its badge/overlay) opens the file picker.
 * - The chosen file is validated client-side (type + size) for instant
 *   feedback, then shown as a local preview immediately.
 * - The file is uploaded via AJAX (no page reload); on success the
 *   avatar, topbar avatar, and status line are updated in place; on
 *   failure the previous picture is restored and an error is shown.
 */
const SH_MAX_PICTURE_BYTES = 2 * 1024 * 1024; // 2MB, matches server-side limit
const SH_ALLOWED_PICTURE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('pictureInput');
  const uploader = document.getElementById('avatarUploader');
  const statusEl = document.getElementById('avatarUploadStatus');
  if (!input || !uploader) return;

  const wrap = document.getElementById('profilePicturePreviewWrapper');
  const originalHtml = wrap ? wrap.innerHTML : '';

  const setStatus = (message, kind) => {
    if (!statusEl) return;
    statusEl.textContent = message || '';
    statusEl.classList.remove('text-danger-status', 'text-success-status');
    if (kind === 'error') statusEl.classList.add('text-danger-status');
    if (kind === 'success') statusEl.classList.add('text-success-status');
  };

  input.addEventListener('change', async () => {
    if (!input.files.length) return;
    const file = input.files[0];

    uploader.classList.remove('is-error', 'is-success');

    // Client-side validation mirrors the server so the user gets instant
    // feedback instead of waiting on a round trip.
    if (!SH_ALLOWED_PICTURE_TYPES.includes(file.type)) {
      uploader.classList.add('is-error');
      setStatus('Only JPG, PNG, or WEBP images are allowed.', 'error');
      shToast('Only JPG, PNG, or WEBP images are allowed.', 'danger');
      input.value = '';
      return;
    }
    if (file.size > SH_MAX_PICTURE_BYTES) {
      uploader.classList.add('is-error');
      setStatus('Image must be smaller than 2MB.', 'error');
      shToast('Image must be smaller than 2MB.', 'danger');
      input.value = '';
      return;
    }

    // Instant local preview while the upload is in flight.
    const reader = new FileReader();
    reader.onload = (e) => {
      if (wrap) wrap.innerHTML = `<img src="${e.target.result}" id="profileImgPreview">`;
    };
    reader.readAsDataURL(file);

    uploader.classList.add('is-loading');
    setStatus('Uploading…');

    const formData = new FormData(document.getElementById('pictureForm'));
    formData.set('action', 'upload_picture');

    let data = null;
    try {
      data = await shAjax(`${window.API_BASE}/employees`, { method: 'POST', body: formData });
    } finally {
      uploader.classList.remove('is-loading');
    }

    if (data && data.success) {
      uploader.classList.add('is-success');
      setStatus('Photo updated.', 'success');
      shToast(data.message, 'success');

      if (wrap) {
        wrap.innerHTML = `<img src="${data.url}" id="profileImgPreview">`;
      }
      const topbarAvatar = document.getElementById('topbarAvatar');
      if (topbarAvatar) {
        topbarAvatar.classList.add('sh-avatar', 'sh-avatar-img');
        topbarAvatar.innerHTML = `<img src="${data.url}" alt="Profile photo">`;
      }
      setTimeout(() => uploader.classList.remove('is-success'), 2000);
    } else {
      uploader.classList.add('is-error');
      const message = (data && data.message) || 'Failed to upload photo. Please try again.';
      setStatus(message, 'error');
      if (data) shToast(message, 'danger');
      // Restore whatever was shown before this attempt.
      if (wrap) wrap.innerHTML = originalHtml;
    }

    input.value = '';
  });
});
