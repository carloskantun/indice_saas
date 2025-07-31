<!-- Modal Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit"></i>
                    <?= $lang['edit_user'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="editUserId" name="user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editUserName" class="form-label"><?= $lang['name'] ?> *</label>
                                <input type="text" class="form-control" id="editUserName" name="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editUserEmail" class="form-label"><?= $lang['email'] ?> *</label>
                                <input type="email" class="form-control" id="editUserEmail" name="email" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editUserStatus" class="form-label"><?= $lang['status'] ?></label>
                                <select class="form-select" id="editUserStatus" name="status">
                                    <option value="active"><?= $lang['active'] ?></option>
                                    <option value="inactive"><?= $lang['inactive'] ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= $lang['user_info'] ?></label>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3" id="editUserAvatar">
                                        <!-- Se llenará dinámicamente -->
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            ID: <span id="editUserIdDisplay"></span><br>
                                            <?= $lang['created_at'] ?>: <span id="editUserCreatedAt"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-key"></i>
                                <?= $lang['change_password'] ?>
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="togglePasswordChange()">
                                <i class="fas fa-lock"></i> <?= $lang['change_password'] ?>
                            </button>
                        </div>
                    </div>

                    <div id="passwordChangeSection" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editUserPassword" class="form-label"><?= $lang['new_password'] ?></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editUserPassword" name="password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('editUserPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text"><?= $lang['password_requirements'] ?></div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editUserPasswordConfirm" class="form-label"><?= $lang['confirm_password'] ?></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editUserPasswordConfirm" name="password_confirm">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('editUserPasswordConfirm')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            <i class="fas fa-building"></i>
                            <?= $lang['company_assignments'] ?>
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewUserCompanies()">
                            <i class="fas fa-eye"></i> <?= $lang['view_current_assignments'] ?>
                        </button>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?= $lang['edit_user_company_info'] ?>
                    </div>

                    <div id="currentCompaniesInfo">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?= $lang['cancel'] ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $lang['save_changes'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
