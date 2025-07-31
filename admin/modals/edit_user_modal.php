<!-- Modal para editar usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i> <?php echo $lang['edit_user']; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i> <?php echo $lang['name']; ?>
                        </label>
                        <input type="text" class="form-control" id="editUserName" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i> <?php echo $lang['email']; ?>
                        </label>
                        <input type="email" class="form-control" id="editUserEmail" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="editUserRole" class="form-label">
                            <i class="fas fa-user-shield me-1"></i> <?php echo $lang['role']; ?>
                        </label>
                        <select class="form-select" id="editUserRole" name="new_role" required>
                            <?php if ($_SESSION['current_role'] === 'superadmin'): ?>
                                <option value="superadmin"><?php echo $lang['superadmin']; ?></option>
                            <?php endif; ?>
                            <option value="admin"><?php echo $lang['admin']; ?></option>
                            <option value="moderator"><?php echo $lang['moderator']; ?></option>
                            <option value="user"><?php echo $lang['user']; ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar me-1"></i> <?php echo $lang['joined_date']; ?>
                        </label>
                        <input type="text" class="form-control" id="editUserJoinedDate" readonly>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $lang['role_change_warning']; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang['cancel']; ?>
                </button>
                <button type="button" class="btn btn-gradient" onclick="updateUserRole()">
                    <i class="fas fa-save me-2"></i> <?php echo $lang['save_changes']; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para suspender usuario -->
<div class="modal fade" id="suspendUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-user-slash me-2"></i> <?php echo $lang['suspend_user']; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $lang['suspend_user_warning']; ?>
                </div>
                <p><?php echo $lang['suspend_user_confirmation']; ?></p>
                <input type="hidden" id="suspendUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang['cancel']; ?>
                </button>
                <button type="button" class="btn btn-warning" onclick="confirmSuspendUser()">
                    <i class="fas fa-user-slash me-2"></i> <?php echo $lang['suspend']; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para activar usuario -->
<div class="modal fade" id="activateUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-check me-2"></i> <?php echo $lang['activate_user']; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $lang['activate_user_info']; ?>
                </div>
                <p><?php echo $lang['activate_user_confirmation']; ?></p>
                <input type="hidden" id="activateUserId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang['cancel']; ?>
                </button>
                <button type="button" class="btn btn-success" onclick="confirmActivateUser()">
                    <i class="fas fa-user-check me-2"></i> <?php echo $lang['activate']; ?>
                </button>
            </div>
        </div>
    </div>
</div>
