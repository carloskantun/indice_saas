<!-- Modal Agregar Plan -->
<div class="modal fade" id="addPlanModal" tabindex="-1" aria-labelledby="addPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPlanModalLabel">
                    <i class="fas fa-plus"></i>
                    <?= $lang['add_new_plan'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPlanForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_name" class="form-label"><?= $lang['plan_name'] ?> *</label>
                                <input type="text" class="form-control" id="add_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add_price_monthly" class="form-label"><?= $lang['monthly_price'] ?> (USD) *</label>
                                <input type="number" class="form-control" id="add_price_monthly" name="price_monthly" 
                                       min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label"><?= $lang['plan_description'] ?></label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="add_users_max" class="form-label"><?= $lang['max_users'] ?></label>
                                <input type="number" class="form-control" id="add_users_max" name="users_max" 
                                       min="-1" value="1" title="Usar -1 para ilimitado">
                                <small class="text-muted">-1 = <?= $lang['unlimited'] ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="add_units_max" class="form-label"><?= $lang['max_units'] ?></label>
                                <input type="number" class="form-control" id="add_units_max" name="units_max" 
                                       min="-1" value="1" title="Usar -1 para ilimitado">
                                <small class="text-muted">-1 = <?= $lang['unlimited'] ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="add_businesses_max" class="form-label"><?= $lang['max_businesses'] ?></label>
                                <input type="number" class="form-control" id="add_businesses_max" name="businesses_max" 
                                       min="-1" value="1" title="Usar -1 para ilimitado">
                                <small class="text-muted">-1 = <?= $lang['unlimited'] ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="add_storage_max_mb" class="form-label"><?= $lang['max_storage'] ?></label>
                                <input type="number" class="form-control" id="add_storage_max_mb" name="storage_max_mb" 
                                       min="-1" value="100" title="Usar -1 para ilimitado">
                                <small class="text-muted">-1 = <?= $lang['unlimited'] ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['modules_included'] ?></label>
                        <div class="border rounded p-3">
                            <div class="row">
                                <?php foreach ($availableModules as $key => $label): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="add_module_<?= $key ?>" 
                                                   name="modules_included[]" 
                                                   value="<?= $key ?>">
                                            <label class="form-check-label" for="add_module_<?= $key ?>">
                                                <?= $label ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllModules('add')">
                                    <?= $lang['all_modules'] ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllModules('add')">
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active" checked>
                            <label class="form-check-label" for="add_is_active">
                                <?= $lang['is_active'] ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= $lang['cancel'] ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        <?= $lang['create'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
