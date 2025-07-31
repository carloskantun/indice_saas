<!-- Modal Agregar Módulo -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModuleModalLabel">
                    <i class="fas fa-plus-square"></i>
                    <?= $lang['add_new_module'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addModuleForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="moduleName" class="form-label"><?= $lang['module_name'] ?> *</label>
                                        <input type="text" class="form-control" id="moduleName" name="name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="moduleSlug" class="form-label"><?= $lang['module_slug'] ?> *</label>
                                        <input type="text" class="form-control" id="moduleSlug" name="slug" required>
                                        <div class="form-text">Identificador único (ej: gastos, usuarios)</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="moduleDescription" class="form-label"><?= $lang['module_description'] ?> *</label>
                                <textarea class="form-control" id="moduleDescription" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="moduleIcon" class="form-label"><?= $lang['module_icon'] ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i id="iconPreview" class="fas fa-puzzle-piece"></i>
                                            </span>
                                            <input type="text" class="form-control" id="moduleIcon" name="icon" 
                                                   value="fas fa-puzzle-piece" onchange="updateIconPreview()">
                                        </div>
                                        <div class="form-text">Clase de Font Awesome (ej: fas fa-users)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="moduleColor" class="form-label"><?= $lang['module_color'] ?></label>
                                        <input type="color" class="form-control color-picker" 
                                               id="moduleColor" name="color" value="#3498db" onchange="updateModulePreview()">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="moduleStatus" class="form-label"><?= $lang['status'] ?></label>
                                <select class="form-select" id="moduleStatus" name="status">
                                    <option value="active"><?= $lang['active'] ?></option>
                                    <option value="inactive"><?= $lang['inactive'] ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Vista previa</label>
                            <div class="module-preview" id="modulePreview">
                                <div class="module-icon" id="previewIcon" style="background-color: #3498db;">
                                    <i class="fas fa-puzzle-piece"></i>
                                </div>
                                <h6 id="previewName">Nombre del Módulo</h6>
                                <p class="text-muted small mb-0" id="previewDescription">Descripción del módulo...</p>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Módulos predefinidos</label>
                                <div class="list-group">
                                    <button type="button" class="list-group-item list-group-item-action" 
                                            onclick="loadSystemModule('gastos')">
                                        <i class="fas fa-coins text-danger me-2"></i>
                                        Gastos
                                    </button>
                                    <button type="button" class="list-group-item list-group-item-action" 
                                            onclick="loadSystemModule('mantenimiento')">
                                        <i class="fas fa-tools text-warning me-2"></i>
                                        Mantenimiento
                                    </button>
                                    <button type="button" class="list-group-item list-group-item-action" 
                                            onclick="loadSystemModule('servicio_cliente')">
                                        <i class="fas fa-headset text-info me-2"></i>
                                        Servicio Cliente
                                    </button>
                                    <button type="button" class="list-group-item list-group-item-action" 
                                            onclick="loadSystemModule('usuarios')">
                                        <i class="fas fa-users text-primary me-2"></i>
                                        Usuarios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?= $lang['cancel'] ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?= $lang['create'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
