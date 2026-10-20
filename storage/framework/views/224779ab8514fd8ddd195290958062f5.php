<?php $__env->startSection('meta_robots', 'noindex, follow'); ?>
<?php $__env->startSection('title', 'Algo salió mal'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('errors.parcial', [
        'codigo' => '500',
        'titulo' => 'Algo salió mal de nuestro lado',
        'texto' => 'El problema quedó registrado y no necesitas repetir ningún pago: si llegaste a finalizar una transferencia o un cobro, tu pedido sigue guardado en tu cuenta. Espera unos minutos, recarga la página y vuelve a intentarlo.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/500.blade.php ENDPATH**/ ?>