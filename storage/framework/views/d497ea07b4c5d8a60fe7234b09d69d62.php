<?php $__env->startSection('meta_robots', 'noindex, follow'); ?>
<?php $__env->startSection('title', 'Demasiados intentos'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('errors.parcial', [
        'codigo' => '429',
        'titulo' => 'Demasiados intentos seguidos',
        'texto' => 'Hemos bloqueado temporalmente las peticiones desde tu conexión para proteger las cuentas. Espera unos minutos y vuelve a intentarlo; si necesitas ayuda, escríbenos.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/429.blade.php ENDPATH**/ ?>