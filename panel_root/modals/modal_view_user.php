<!-- Modal Ver Usuario -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">
                    <i class="fas fa-user"></i>
                    <?= $lang['user_details'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="user-avatar mx-auto mb-3" id="viewUserAvatar" style="width: 80px; height: 80px; font-size: 1.5rem;">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                                <h5 id="viewUserName"></h5>
                                <p class="text-muted" id="viewUserEmail"></p>
                                <span class="badge fs-6" id="viewUserStatus"></span>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    <?= $lang['basic_info'] ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>ID:</strong> <span id="viewUserId"></span>
                                </div>
                                <div class="mb-2">
                                    <strong><?= $lang['created_at'] ?>:</strong>
                                    <span id="viewUserCreatedAt"></span>
                                </div>
                                <div class="mb-2">
                                    <strong><?= $lang['last_access'] ?>:</strong>
                                    <span id="viewUserLastAccess"></span>
                                </div>
                                <div class="mb-2">
                                    <strong><?= $lang['total_companies'] ?>:</strong>
                                    <span id="viewUserCompaniesCount"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-building"></i>
                                    <?= $lang['company_assignments'] ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th><?= $lang['company'] ?></th>
                                                <th><?= $lang['role'] ?></th>
                                                <th><?= $lang['status'] ?></th>
                                                <th><?= $lang['assigned_at'] ?></th>
                                                <th><?= $lang['last_access'] ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="viewUserCompaniesTable">
                                            <!-- Se llenará dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-line"></i>
                                    <?= $lang['activity_summary'] ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-primary" id="viewUserActiveCompanies">0</h4>
                                            <small><?= $lang['active_companies'] ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-warning" id="viewUserTotalRoles">0</h4>
                                            <small><?= $lang['total_roles'] ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-success" id="viewUserAdminRoles">0</h4>
                                            <small><?= $lang['admin_roles'] ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-info" id="viewUserRecentAccess">0</h4>
                                            <small><?= $lang['recent_accesses'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-history"></i>
                                    <?= $lang['recent_activity'] ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="viewUserRecentActivity">
                                    <p class="text-muted text-center">
                                        <i class="fas fa-clock"></i>
                                        <?= $lang['loading_activity'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?= $lang['close'] ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="editUserFromView()">
                    <i class="fas fa-edit"></i> <?= $lang['edit_user'] ?>
                </button>
                <button type="button" class="btn btn-warning" onclick="manageUserRolesFromView()">
                    <i class="fas fa-user-cog"></i> <?= $lang['manage_roles'] ?>
                </button>
            </div>
        </div>
    </div>
</div>
