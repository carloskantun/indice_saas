<!-- Modal Editar Módulo -->
<div class="modal fade" id="editModuleModal" tabindex="-1" aria-labelledby="editModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModuleModalLabel">
                    <i class="fas fa-edit"></i>
                    <?= $lang['edit_module'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editModuleForm">
                <input type="hidden" id="editModuleId" name="module_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editModuleName" class="form-label"><?= $lang['module_name'] ?> *</label>
                                        <input type="text" class="form-control" id="editModuleName" name="name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editModuleSlug" class="form-label"><?= $lang['module_slug'] ?> *</label>
                                        <input type="text" class="form-control" id="editModuleSlug" name="slug" required>
                                        <div class="form-text">Identificador único (ej: gastos, usuarios)</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="editModuleDescription" class="form-label"><?= $lang['module_description'] ?> *</label>
                                <textarea class="form-control" id="editModuleDescription" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editModuleIcon" class="form-label"><?= $lang['module_icon'] ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i id="editIconPreview" class="fas fa-puzzle-piece"></i>
                                            </span>
                                            <input type="text" class="form-control" id="editModuleIcon" name="icon" 
                                                   onchange="updateEditIconPreview()">
                                        </div>
                                        <div class="form-text">Clase de Font Awesome (ej: fas fa-users)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editModuleColor" class="form-label"><?= $lang['module_color'] ?></label>
                                        <input type="color" class="form-control color-picker" 
                                               id="editModuleColor" name="color" onchange="updateEditModulePreview()">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="editModuleStatus" class="form-label"><?= $lang['status'] ?></label>
                                <select class="form-select" id="editModuleStatus" name="status">
                                    <option value="active"><?= $lang['active'] ?></option>
                                    <option value="inactive"><?= $lang['inactive'] ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= $lang['module_details'] ?></label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>ID:</strong> <span id="editModuleIdDisplay"></span><br>
                                            <strong><?= $lang['created_at'] ?>:</strong> <span id="editModuleCreatedAt"></span>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Planes usando:</strong> <span id="editModulePlansCount"></span><br>
                                            <strong>Estado actual:</strong> <span id="editModuleCurrentStatus"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Vista previa</label>
                            <div class="module-preview" id="editModulePreview">
                                <div class="module-icon" id="editPreviewIcon" style="background-color: #3498db;">
                                    <i class="fas fa-puzzle-piece"></i>
                                </div>
                                <h6 id="editPreviewName">Nombre del Módulo</h6>
                                <p class="text-muted small mb-0" id="editPreviewDescription">Descripción del módulo...</p>
                            </div>

                            <div class="mt-3" id="editCurrentPlans">
                                <!-- Se llenará dinámicamente con los planes que usan este módulo -->
                            </div>
                        </div>
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
