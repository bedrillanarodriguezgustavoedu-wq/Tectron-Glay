<?php $__env->startSection('meta_robots', 'noindex, follow'); ?>
<?php $__env->startSection('title', 'Estamos de mantenimiento'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('errors.parcial', [
        'codigo' => '503',
        'titulo' => 'La tienda está en mantenimiento',
        'texto' => 'Estamos ajustando algo y en unos minutos todo vuelve a estar disponible. Tus pedidos y pagos quedan guardados; no hace falta que repitas ninguno.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/503.blade.php ENDPATH**/ ?>