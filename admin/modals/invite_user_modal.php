<!-- Modal para invitar usuario -->
<div class="modal fade" id="inviteUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i> <?php echo $lang['invite_user']; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="inviteUserForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inviteEmail" class="form-label">
                                    <i class="fas fa-envelope me-1"></i> <?php echo $lang['email']; ?>
                                </label>
                                <input type="email" class="form-control" id="inviteEmail" name="email" required>
                                <div class="invalid-feedback" id="emailError"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inviteRole" class="form-label">
                                    <i class="fas fa-user-shield me-1"></i> <?php echo $lang['role']; ?>
                                </label>
                                <select class="form-select" id="inviteRole" name="role" required>
                                    <option value=""><?php echo $lang['select_role']; ?></option>
                                    <?php if ($_SESSION['current_role'] === 'superadmin'): ?>
                                        <option value="superadmin"><?php echo $lang['superadmin']; ?></option>
                                    <?php endif; ?>
                                    <option value="admin"><?php echo $lang['admin']; ?></option>
                                    <option value="moderator"><?php echo $lang['moderator']; ?></option>
                                    <option value="user"><?php echo $lang['user']; ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inviteUnit" class="form-label">
                                    <i class="fas fa-building me-1"></i> <?php echo $lang['unit']; ?> 
                                    <small class="text-muted">(<?php echo $lang['optional']; ?>)</small>
                                </label>
                                <select class="form-select" id="inviteUnit" name="unit_id">
                                    <option value=""><?php echo $lang['select_unit']; ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inviteBusiness" class="form-label">
                                    <i class="fas fa-store me-1"></i> <?php echo $lang['business']; ?> 
                                    <small class="text-muted">(<?php echo $lang['optional']; ?>)</small>
                                </label>
                                <select class="form-select" id="inviteBusiness" name="business_id" disabled>
                                    <option value=""><?php echo $lang['select_business']; ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo $lang['invitation_info']; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo $lang['cancel']; ?>
                </button>
                <button type="button" class="btn btn-gradient" onclick="sendInvitation()">
                    <i class="fas fa-paper-plane me-2"></i> <?php echo $lang['send_invitation']; ?>
                </button>
            </div>
        </div>
    </div>
</div>
