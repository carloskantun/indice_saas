<!-- Modal Ver Módulo -->
<div class="modal fade" id="viewModuleModal" tabindex="-1" aria-labelledby="viewModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModuleModalLabel">
                    <i class="fas fa-puzzle-piece"></i>
                    <?= $lang['module_details'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="module-icon mx-auto mb-3" id="viewModuleIcon" style="width: 80px; height: 80px; font-size: 2rem;">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                                <h4 id="viewModuleName"></h4>
                                <p class="text-muted" id="viewModuleSlug"></p>
                                <span class="badge fs-6" id="viewModuleStatus"></span>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    Información Básica
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>ID:</strong> <span id="viewModuleId"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Slug:</strong> <code id="viewModuleSlugCode"></code>
                                </div>
                                <div class="mb-2">
                                    <strong>Ícono:</strong> <code id="viewModuleIconClass"></code>
                                </div>
                                <div class="mb-2">
                                    <strong>Color:</strong> 
                                    <span class="color-picker" id="viewModuleColorDisplay" style="width: 20px; height: 20px; display: inline-block; border-radius: 3px;"></span>
                                    <code id="viewModuleColorCode"></code>
                                </div>
                                <div class="mb-2">
                                    <strong><?= $lang['created_at'] ?>:</strong>
                                    <span id="viewModuleCreatedAt"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-align-left"></i>
                                    Descripción
                                </h6>
                            </div>
                            <div class="card-body">
                                <p id="viewModuleDescription"></p>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-layer-group"></i>
                                    <?= $lang['plans_using_module'] ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="viewModulePlans">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-bar"></i>
                                    Estadísticas de Uso
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-primary" id="viewModulePlansCount">0</h4>
                                            <small>Planes usando</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-success" id="viewModuleCompaniesCount">0</h4>
                                            <small>Empresas con acceso</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-info" id="viewModuleUsersCount">0</h4>
                                            <small>Usuarios potenciales</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-warning" id="viewModuleAvailability">0%</h4>
                                            <small><?= $lang['module_availability'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-building"></i>
                                    Empresas con Acceso al Módulo
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Empresa</th>
                                                <th>Plan</th>
                                                <th>Estado</th>
                                                <th>Usuarios</th>
                                                <th>Último Acceso</th>
                                            </tr>
                                        </thead>
                                        <tbody id="viewModuleCompaniesTable">
                                            <!-- Se llenará dinámicamente -->
                                        </tbody>
                                    </table>
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
                <button type="button" class="btn btn-primary" onclick="editModuleFromView()">
                    <i class="fas fa-edit"></i> <?= $lang['edit_module'] ?>
                </button>
                <?php if (checkRole(['root'])): ?>
                <button type="button" class="btn btn-warning" onclick="toggleModuleStatusFromView()">
                    <i class="fas fa-power-off"></i> Cambiar Estado
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
