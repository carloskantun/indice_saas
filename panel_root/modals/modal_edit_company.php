<!-- Modal Editar Empresa -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCompanyModalLabel">
                    <i class="fas fa-edit"></i>
                    <?= $lang['edit_company'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCompanyForm">
                <input type="hidden" id="edit_company_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="edit_company_name" class="form-label"><?= $lang['company_name'] ?> *</label>
                                <input type="text" class="form-control" id="edit_company_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_company_status" class="form-label"><?= $lang['company_status'] ?></label>
                                <select class="form-select" id="edit_company_status" name="status">
                                    <option value="active">Activa</option>
                                    <option value="inactive">Inactiva</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_company_description" class="form-label"><?= $lang['company_description'] ?></label>
                        <textarea class="form-control" id="edit_company_description" name="description" rows="3"></textarea>
                    </div>

                    <!-- Información del plan actual -->
                    <div class="card bg-light mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-layer-group"></i>
                                Plan Actual
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="current_plan_info">
                                <!-- Se llena dinámicamente -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openChangePlanModal()">
                                <i class="fas fa-exchange-alt"></i>
                                <?= $lang['change_plan'] ?>
                            </button>
                        </div>
                    </div>

                    <!-- Estadísticas de uso -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-bar"></i>
                                <?= $lang['usage_statistics'] ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="usage_stats">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= $lang['cancel'] ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $lang['save'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
