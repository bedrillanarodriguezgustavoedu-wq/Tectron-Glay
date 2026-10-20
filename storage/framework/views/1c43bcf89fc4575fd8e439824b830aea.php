

<?php $__env->startSection('content'); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Roles y permisos</h1>
            <p class="text-muted mb-0">Define qué puede hacer cada perfil en el administrador web.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Crear Rol')): ?>
            <a href="<?php echo e(route('admin.roles.create')); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Crear rol
            </a>
        <?php endif; ?>
    </div>

    <?php if(session('mensaje')): ?>
        <div class="alert alert-<?php echo e(session('icono') === 'success' ? 'success' : 'info'); ?>" role="status">
            <?php echo e(session('mensaje')); ?>

        </div>
    <?php endif; ?>
    <?php if($errors->has('role')): ?>
        <div class="alert alert-warning" role="alert"><?php echo e($errors->first('role')); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Perfiles registrados</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Rol</th>
                            <th scope="col">Usuarios</th>
                            <th scope="col">Permisos</th>
                            <th scope="col" class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-semibold"><?php echo e($role->name); ?></span>
                                    <?php if($role->name === 'SUPER ADMIN'): ?>
                                        <span class="badge text-bg-dark ms-2">Protegido</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($role->users_count); ?></td>
                                <td><?php echo e($role->permissions_count); ?></td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Ver detalle de Rol')): ?>
                                            <a href="<?php echo e(route('admin.roles.show', $role->id)); ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Ver <?php echo e($role->name); ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($role->name !== 'SUPER ADMIN'): ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Ver detalle de Permisos de rol')): ?>
                                                <a href="<?php echo e(route('admin.roles.permisos', $role->id)); ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Permisos
                                                </a>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Editar Rol')): ?>
                                                <a href="<?php echo e(route('admin.roles.edit', $role->id)); ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Editar <?php echo e($role->name); ?></span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Eliminar Rol')): ?>
                                                <form action="<?php echo e(route('admin.roles.destroy', $role->id)); ?>" method="POST"
                                                    onsubmit="return confirm('¿Eliminar el rol <?php echo e($role->name); ?>?');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        aria-label="Eliminar <?php echo e($role->name); ?>">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">Todavía no hay roles registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if($roles->hasPages()): ?>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    Mostrando <?php echo e($roles->firstItem()); ?>–<?php echo e($roles->lastItem()); ?> de <?php echo e($roles->total()); ?> roles
                </span>
                <?php echo e($roles->links('pagination::bootstrap-4')); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>