<?php $__env->startSection('content'); ?>
    <!-- Page Title -->
    <div class="page-title light-background">
        <div class="container d-lg-flex justify-content-between align-items-center">
            <h1 class="mb-2 mb-lg-0">404</h1>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="<?php echo e(url('/')); ?>">Principal</a></li>
                    <li class="current">404</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Error 404 Section -->
    <section id="error-404" class="error-404 section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="text-center">
                <div class="error-icon mb-4" data-aos="zoom-in" data-aos-delay="200">
                    <i class="bi bi-exclamation-circle"></i>
                </div>

                <h1 class="error-code mb-4" data-aos="fade-up" data-aos-delay="300">404</h1>

                <h2 class="error-title mb-3" data-aos="fade-up" data-aos-delay="400">¡ups! Página no encontrada</h2>

                <p class="error-text mb-4" data-aos="fade-up" data-aos-delay="500">
                    Es posible que la página que está buscando haya sido eliminada, haya cambiado su nombre o esté temporalmente no disponible
                </p>


                <div class="error-action" data-aos="fade-up" data-aos-delay="700">
                    <a href="<?php echo e(url('/')); ?>" class="btn btn-primary">Volver a la pagina de inicio</a>
                </div>
            </div>

        </div>

    </section><!-- /Error 404 Section -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/404.blade.php ENDPATH**/ ?>