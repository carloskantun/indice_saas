<!-- Modal Gestionar Roles de Usuario -->
<div class="modal fade" id="manageUserRolesModal" tabindex="-1" aria-labelledby="manageUserRolesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageUserRolesModalLabel">
                    <i class="fas fa-user-cog"></i>
                    <?= $lang['manage_user_roles'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="manageRolesUserId">
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="user-avatar mx-auto mb-3" id="manageRolesUserAvatar" style="width: 60px; height: 60px;">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                                <h6 id="manageRolesUserName"></h6>
                                <small class="text-muted" id="manageRolesUserEmail"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-pie"></i>
                                    <?= $lang['current_role_summary'] ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row" id="currentRoleSummary">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-building"></i>
                            <?= $lang['company_role_assignments'] ?>
                        </h6>
                        <button type="button" class="btn btn-sm btn-success" onclick="addNewCompanyRole()">
                            <i class="fas fa-plus"></i> <?= $lang['add_company_role'] ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= $lang['company'] ?></th>
                                        <th><?= $lang['current_role'] ?></th>
                                        <th><?= $lang['new_role'] ?></th>
                                        <th><?= $lang['status'] ?></th>
                                        <th><?= $lang['assigned_at'] ?></th>
                                        <th><?= $lang['actions'] ?></th>
                                    </tr>
                                </thead>
                                <tbody id="companyRolesTable">
                                    <!-- Se llenará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-plus-circle"></i>
                            <?= $lang['add_new_company_assignment'] ?>
                        </h6>
                    </div>
                    <div class="card-body" id="newCompanyAssignmentSection" style="display: none;">
                        <form id="newCompanyRoleForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="newCompanySelect" class="form-label"><?= $lang['select_company'] ?></label>
                                        <select class="form-select" id="newCompanySelect" name="company_id" required>
                                            <option value=""><?= $lang['select_company'] ?></option>
                                            <!-- Se llenará dinámicamente -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="newRoleSelect" class="form-label"><?= $lang['select_role'] ?></label>
                                        <select class="form-select" id="newRoleSelect" name="role" required>
                                            <option value=""><?= $lang['select_role'] ?></option>
                                            <option value="superadmin">Superadministrador</option>
                                            <option value="admin">Administrador</option>
                                            <option value="moderator">Moderador</option>
                                            <option value="user">Usuario</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-success d-block w-100">
                                            <i class="fas fa-plus"></i> <?= $lang['add'] ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <h6>
                        <i class="fas fa-info-circle"></i>
                        <?= $lang['role_management_info'] ?>
                    </h6>
                    <ul class="mb-0">
                        <li><?= $lang['role_hierarchy_info'] ?></li>
                        <li><?= $lang['role_permissions_info'] ?></li>
                        <li><?= $lang['role_access_info'] ?></li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?= $lang['close'] ?>
                </button>
                <button type="button" class="btn btn-warning" onclick="resetAllChanges()">
                    <i class="fas fa-undo"></i> <?= $lang['reset_changes'] ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveAllRoleChanges()">
                    <i class="fas fa-save"></i> <?= $lang['save_all_changes'] ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template para fila de rol de empresa -->
<template id="companyRoleRowTemplate">
    <tr data-company-id="">
        <td>
            <div class="d-flex align-items-center">
                <i class="fas fa-building text-primary me-2"></i>
                <span class="company-name"></span>
            </div>
        </td>
        <td>
            <span class="current-role-badge"></span>
        </td>
        <td>
            <select class="form-select form-select-sm new-role-select">
                <option value="superadmin">Superadministrador</option>
                <option value="admin">Administrador</option>
                <option value="moderator">Moderador</option>
                <option value="user">Usuario</option>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm status-select">
                <option value="active"><?= $lang['active'] ?></option>
                <option value="inactive"><?= $lang['inactive'] ?></option>
            </select>
        </td>
        <td class="assigned-date"></td>
        <td>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeCompanyRole(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
</template>
