
<div class="page-title light-background">
    <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0"><?php echo e($codigo); ?></h1>
        <nav class="breadcrumbs">
            <ol>
                <li><a href="<?php echo e(url('/')); ?>">Principal</a></li>
                <li class="current"><?php echo e($codigo); ?></li>
            </ol>
        </nav>
    </div>
</div><!-- End Page Title -->

<section id="error-404" class="error-404 section">

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="text-center">
            <div class="error-icon mb-4" data-aos="zoom-in" data-aos-delay="200">
                <i class="bi bi-exclamation-circle"></i>
            </div>

            <h1 class="error-code mb-4" data-aos="fade-up" data-aos-delay="300"><?php echo e($codigo); ?></h1>

            <h2 class="error-title mb-3" data-aos="fade-up" data-aos-delay="400"><?php echo e($titulo); ?></h2>

            <p class="error-text mb-4" data-aos="fade-up" data-aos-delay="500">
                <?php echo e($texto); ?>

            </p>

            <div class="error-action" data-aos="fade-up" data-aos-delay="700">
                <a href="<?php echo e(url('/')); ?>" class="btn btn-primary">Volver a la pagina de inicio</a>
            </div>
        </div>

    </div>

</section><!-- End Error Section -->
<?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/errors/parcial.blade.php ENDPATH**/ ?>