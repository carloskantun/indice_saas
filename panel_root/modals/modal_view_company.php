<!-- Modal Ver Empresa -->
<div class="modal fade" id="viewCompanyModal" tabindex="-1" aria-labelledby="viewCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCompanyModalLabel">
                    <i class="fas fa-eye"></i>
                    <?= $lang['company_details'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Información básica -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h4 id="view_company_name"></h4>
                        <p id="view_company_description" class="text-muted"></p>
                        <div class="d-flex gap-2">
                            <span id="view_company_status_badge"></span>
                            <span id="view_company_plan_badge"></span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-1"><strong>ID:</strong> <span id="view_company_id"></span></p>
                        <p class="mb-1"><strong>Creado:</strong> <span id="view_company_created_at"></span></p>
                        <p class="mb-0"><strong>Por:</strong> <span id="view_company_created_by"></span></p>
                    </div>
                </div>

                <!-- Estadísticas en cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4 id="view_users_count" class="text-primary">0</h4>
                                <p class="mb-0">Usuarios</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <i class="fas fa-building fa-2x text-info mb-2"></i>
                                <h4 id="view_units_count" class="text-info">0</h4>
                                <p class="mb-0">Unidades</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <i class="fas fa-store fa-2x text-success mb-2"></i>
                                <h4 id="view_businesses_count" class="text-success">0</h4>
                                <p class="mb-0">Negocios</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <i class="fas fa-layer-group fa-2x text-warning mb-2"></i>
                                <h4 id="view_modules_count" class="text-warning">0</h4>
                                <p class="mb-0">Módulos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles del plan -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-layer-group"></i>
                                    <?= $lang['plan_limits'] ?>
                                </h6>
                            </div>
                            <div class="card-body" id="view_plan_limits">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-puzzle-piece"></i>
                                    Módulos Incluidos
                                </h6>
                            </div>
                            <div class="card-body" id="view_plan_modules">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usuarios recientes -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-users"></i>
                                    Usuarios de la Empresa
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                                <th>Estado</th>
                                                <th>Último Acceso</th>
                                            </tr>
                                        </thead>
                                        <tbody id="view_company_users">
                                            <!-- Se llena dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unidades y negocios -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-building"></i>
                                    Unidades
                                </h6>
                            </div>
                            <div class="card-body" id="view_company_units">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-store"></i>
                                    Negocios
                                </h6>
                            </div>
                            <div class="card-body" id="view_company_businesses">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" onclick="editCompanyFromView()">
                    <i class="fas fa-edit"></i>
                    <?= $lang['edit_company'] ?>
                </button>
            </div>
        </div>
    </div>
</div>
