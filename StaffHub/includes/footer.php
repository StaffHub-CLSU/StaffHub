</div><!-- /.sh-content -->
  </div><!-- /.sh-main -->
</div><!-- /.sh-shell -->

<!-- Body-level overlays: kept OUTSIDE .sh-shell so no ancestor stacking
     context / transform can ever trap them under their own backdrop. -->
<div class="sh-toast-container" id="shToastContainer"></div>
<div class="modal fade" id="shLogoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title">Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4">
        <p class="mb-0 sh-muted">Are you sure you want to log out? Your current session will be terminated.</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="shLogoutConfirmBtn">Logout</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= $rootPath ?? '../' ?>assets/js/theme.js"></script>
<script src="<?= $rootPath ?? '../' ?>assets/js/app.js"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>