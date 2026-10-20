<?php $__env->startSection('content'); ?>
    <section class="section py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-md-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                        <div class="legal-content text-dark fs-5" style="line-height: 1.8;">
                            <?php echo $contenido; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/web/legal.blade.php ENDPATH**/ ?>