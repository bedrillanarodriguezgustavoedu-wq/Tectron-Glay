<?php $__env->startSection('meta_robots', 'noindex, follow'); ?>
<?php $__env->startSection('title', 'La sesión caducó'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('errors.parcial', [
        'codigo' => '419',
        'titulo' => '¡ups! La página caducó',
        'texto' => 'Por seguridad, el formulario dejó de aceptar envíos después de un rato sin actividad. Vuelve a la tienda, revisa tu carrito e intenta el envío otra vez.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/419.blade.php ENDPATH**/ ?>