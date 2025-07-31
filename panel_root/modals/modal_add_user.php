<!-- Modal Agregar Usuario -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="fas fa-user-plus"></i>
                    <?= $lang['add_new_user'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userName" class="form-label"><?= $lang['name'] ?> *</label>
                                <input type="text" class="form-control" id="userName" name="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userEmail" class="form-label"><?= $lang['email'] ?> *</label>
                                <input type="email" class="form-control" id="userEmail" name="email" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userPassword" class="form-label"><?= $lang['password'] ?> *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="userPassword" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('userPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text"><?= $lang['password_requirements'] ?></div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userPasswordConfirm" class="form-label"><?= $lang['confirm_password'] ?> *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="userPasswordConfirm" name="password_confirm" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('userPasswordConfirm')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userStatus" class="form-label"><?= $lang['status'] ?></label>
                                <select class="form-select" id="userStatus" name="status">
                                    <option value="active"><?= $lang['active'] ?></option>
                                    <option value="inactive"><?= $lang['inactive'] ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sendCredentials" class="form-label"><?= $lang['notification_options'] ?></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sendCredentials" name="send_credentials" value="1" checked>
                                    <label class="form-check-label" for="sendCredentials">
                                        <?= $lang['send_credentials_email'] ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">
                        <i class="fas fa-building"></i>
                        <?= $lang['company_assignments'] ?>
                    </h6>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['select_companies'] ?></label>
                        <div class="row" id="companiesCheckboxes">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>

                    <div class="mb-3" id="rolesSection" style="display: none;">
                        <label class="form-label"><?= $lang['assign_roles'] ?></label>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <?= $lang['role_assignment_info'] ?>
                        </div>
                        <div id="roleAssignments">
                            <!-- Se llenará dinámicamente basado en empresas seleccionadas -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?= $lang['cancel'] ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?= $lang['create_user'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
