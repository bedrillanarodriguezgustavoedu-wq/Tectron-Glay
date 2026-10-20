

<?php $__env->startSection('content'); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Permisos: <?php echo e($rol->name); ?></h1>
            <p class="text-muted mb-0">Marca las funciones que estarán disponibles para este rol.</p>
        </div>
        <a href="<?php echo e(route('admin.roles.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a roles
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">No se pudieron guardar los permisos:</p>
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo e(route('admin.roles.update_permisos', $rol->id)); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="text-muted"><span id="selected-permissions"><?php echo e($rol->permissions->count()); ?></span> permisos seleccionados</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" type="button" id="select-all-permissions">Seleccionar todos</button>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="clear-permissions">Quitar todos</button>
            </div>
        </div>

        <div class="row g-3">
            <?php $__empty_1 = true; $__currentLoopData = $permisos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modulo => $grupoPermisos): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <section class="card border-0 shadow-sm h-100" aria-label="Permisos de <?php echo e($modulo); ?>">
                        <div class="card-header bg-white">
                            <h2 class="h6 mb-0"><?php echo e($modulo); ?></h2>
                        </div>
                        <div class="card-body">
                            <?php $__currentLoopData = $grupoPermisos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permiso): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permisos[]"
                                        value="<?php echo e($permiso->id); ?>" id="permiso_<?php echo e($permiso->id); ?>"
                                        <?php if($rol->hasPermissionTo($permiso->name, 'web')): echo 'checked'; endif; ?>>
                                    <label class="form-check-label" for="permiso_<?php echo e($permiso->id); ?>">
                                        <?php echo e($permiso->name); ?>

                                    </label>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </section>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">No hay permisos web disponibles para asignar.</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1" aria-hidden="true"></i>Guardar permisos
            </button>
            <a href="<?php echo e(route('admin.roles.index')); ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>

    <script>
        (() => {
            const boxes = [...document.querySelectorAll('.permission-checkbox')];
            const count = document.getElementById('selected-permissions');
            const updateCount = () => count.textContent = boxes.filter(box => box.checked).length;

            boxes.forEach(box => box.addEventListener('change', updateCount));
            document.getElementById('select-all-permissions')?.addEventListener('click', () => {
                boxes.forEach(box => box.checked = true);
                updateCount();
            });
            document.getElementById('clear-permissions')?.addEventListener('click', () => {
                boxes.forEach(box => box.checked = false);
                updateCount();
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/admin/roles/permisos.blade.php ENDPATH**/ ?>