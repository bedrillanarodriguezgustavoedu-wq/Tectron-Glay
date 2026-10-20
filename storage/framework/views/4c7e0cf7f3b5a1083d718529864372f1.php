<?php $__env->startSection('content'); ?>
    <h1>Ajustes del sistema</h1>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Configuraciones del sistema</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(url('/admin/ajustes/create')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="row">

                            <div class="col-md-9">
                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="nombre" class="form-label">Nombre(*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                                <input type="text" name="nombre" id="nombre"
                                                    class="form-control <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    value="<?php echo e(old('nombre', $ajuste->nombre ?? '')); ?>"
                                                    placeholder="Ingrese el nombre de la empresa o sistema" required>
                                                <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label for="descripcion" class="form-label">Descripción(*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                                <input type="text" name="descripcion" id="descripcion"
                                                    class="form-control <?php $__errorArgs = ['descripcion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    value="<?php echo e(old('descripcion', $ajuste->descripcion ?? '')); ?>"
                                                    placeholder="Breve descripción del sistema" required>
                                                <?php $__errorArgs = ['descripcion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="sucursal" class="form-label">Sucursal(*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-shop"></i></span>
                                                <input type="text" name="sucursal" id="sucursal"
                                                    class="form-control <?php $__errorArgs = ['sucursal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    value="<?php echo e(old('sucursal', $ajuste->sucursal ?? '')); ?>" placeholder="Nombre de la sucursal"
                                                    required>
                                                <?php $__errorArgs = ['sucursal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label for="direccion" class="form-label">Dirección(*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                                <textarea name="direccion" id="direccion" rows="1" class="form-control <?php $__errorArgs = ['direccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    placeholder="Dirección física completa" required><?php echo e(old('direccion', $ajuste->direccion ?? '')); ?></textarea>
                                                <?php $__errorArgs = ['direccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="telefonos" class="form-label">Teléfono(*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                                <input type="text" name="telefonos" id="telefonos"
                                                    class="form-control <?php $__errorArgs = ['telefonos'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    value="<?php echo e(old('telefonos', $ajuste->telefono ?? '')); ?>" placeholder="Ej. +51 987654321" required>
                                                <?php $__errorArgs = ['telefonos'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="email" class="form-label">Email(*)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                <input type="email" name="email" id="email"
                                                    class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    value="<?php echo e(old('email', $ajuste->email ?? '')); ?>" placeholder="correo@empresa.com" required>
                                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="pagina_web" class="form-label">Página web (Opcional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                                <input type="text" name="pagina_web" id="pagina_web"
                                                    class="form-control <?php $__errorArgs = ['pagina_web'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    value="<?php echo e(old('pagina_web', $ajuste->pagina_web ?? '')); ?>" placeholder="https://www.ejemplo.com">
                                                <?php $__errorArgs = ['pagina_web'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h5 class="text-primary mb-3"><i class="bi bi-shop"></i> Configuración de la Tienda Principal</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label for="home_slider_velocidad" class="form-label">Velocidad del Slider Principal (ms) (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                                                    <input type="number" name="home_slider_velocidad" id="home_slider_velocidad"
                                                        class="form-control" value="<?php echo e(old('home_slider_velocidad', $ajuste->home_slider_velocidad ?? 5000)); ?>"
                                                        min="1000" step="500" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label for="home_carrusel_velocidad" class="form-label">Velocidad de los Carruseles (ms) (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-arrow-left-right"></i></span>
                                                    <input type="number" name="home_carrusel_velocidad" id="home_carrusel_velocidad"
                                                        class="form-control" value="<?php echo e(old('home_carrusel_velocidad', $ajuste->home_carrusel_velocidad ?? 3000)); ?>"
                                                        min="1000" step="500" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label for="home_max_categorias" class="form-label">Categorías a mostrar en el Home (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-grid-3x3-gap"></i></span>
                                                    <input type="number" name="home_max_categorias" id="home_max_categorias"
                                                        class="form-control" value="<?php echo e(old('home_max_categorias', $ajuste->home_max_categorias ?? 3)); ?>"
                                                        min="1" max="10" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label for="home_slider_altura" class="form-label">Altura del banner principal (px) (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-arrows-vertical"></i></span>
                                                    <input type="number" name="home_slider_altura" id="home_slider_altura"
                                                        class="form-control <?php $__errorArgs = ['home_slider_altura'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('home_slider_altura', $ajuste->home_slider_altura ?? 520)); ?>"
                                                        min="300" max="900" step="10" required>
                                                </div>
                                                <small class="form-text text-muted">De 300 a 900 px; en móviles se adapta automáticamente.</small>
                                                <?php $__errorArgs = ['home_slider_altura'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="home_max_productos_categoria" class="form-label">Max. Productos por Categoría (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                                    <input type="number" name="home_max_productos_categoria" id="home_max_productos_categoria"
                                                        class="form-control" value="<?php echo e(old('home_max_productos_categoria', $ajuste->home_max_productos_categoria ?? 15)); ?>"
                                                        min="1" max="50" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group mb-3">
                                                <label for="home_destacado_oferta_id" class="form-label">Oferta Destacada para el Contador</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                                    <select name="home_destacado_oferta_id" id="home_destacado_oferta_id" class="form-control">
                                                        <option value="">-- Autodetectar oferta activa --</option>
                                                        <?php $__currentLoopData = $ofertas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $oferta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($oferta->id); ?>"
                                                                <?php echo e(old('home_destacado_oferta_id', $ajuste->home_destacado_oferta_id ?? '') == $oferta->id ? 'selected' : ''); ?>>
                                                                <?php echo e($oferta->nombre); ?> (-<?php echo e($oferta->porcentaje_descuento); ?>%)
                                                            </option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label class="form-label d-block">Contador de Ofertas</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="home_mostrar_contador" id="home_mostrar_contador" value="1" 
                                                        <?php echo e(old('home_mostrar_contador', $ajuste->home_mostrar_contador ?? true) ? 'checked' : ''); ?>>
                                                    <label class="form-check-label fw-bold" for="home_mostrar_contador">Mostrar sección</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h5 class="text-primary mb-3"><i class="bi bi-cash-coin"></i> Configuración de Divisas e Importes</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label for="moneda_nombre" class="form-label fw-bold">Nombre de Moneda (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-cash"></i></span>
                                                    <input type="text" name="moneda_nombre" id="moneda_nombre" class="form-control <?php $__errorArgs = ['moneda_nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('moneda_nombre', $ajuste->moneda_nombre ?? 'Sol Peruano')); ?>" placeholder="Ej. Sol Peruano" required>
                                                    <?php $__errorArgs = ['moneda_nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-3">
                                                <label for="divisa" class="form-label fw-bold">Código ISO Divisa (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-currency-exchange"></i></span>
                                                    <select name="divisa" id="divisa" class="form-control <?php $__errorArgs = ['divisa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                                        <option value="">-- Seleccionar --</option>
                                                        <?php $__currentLoopData = $divisas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $div): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($div['symbol']); ?>"
                                                                <?php echo e(old('divisa', $ajuste->divisa ?? '') == $div['symbol'] ? 'selected' : ''); ?>>
                                                                <?php echo e($div['name']); ?> (<?php echo e($div['symbol']); ?>)
                                                            </option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </select>
                                                    <?php $__errorArgs = ['divisa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label for="moneda_simbolo" class="form-label fw-bold">Símbolo (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                                    <input type="text" name="moneda_simbolo" id="moneda_simbolo" class="form-control <?php $__errorArgs = ['moneda_simbolo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('moneda_simbolo', $ajuste->moneda_simbolo ?? 'S/')); ?>" placeholder="Ej. S/" required>
                                                    <small class="form-text text-muted">Escribe solo el símbolo, no el tipo de cambio ni el precio.</small>
                                                    <?php $__errorArgs = ['moneda_simbolo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label for="moneda_decimales" class="form-label fw-bold">Decimales (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-dot"></i></span>
                                                    <input type="number" name="moneda_decimales" id="moneda_decimales" class="form-control <?php $__errorArgs = ['moneda_decimales'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('moneda_decimales', $ajuste->moneda_decimales ?? 2)); ?>" min="0" max="4" required>
                                                    <?php $__errorArgs = ['moneda_decimales'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-3">
                                                <label for="moneda_posicion" class="form-label fw-bold">Posición del Símbolo (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-align-start"></i></span>
                                                    <select name="moneda_posicion" id="moneda_posicion" class="form-control <?php $__errorArgs = ['moneda_posicion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                                        <option value="antes" <?php echo e(old('moneda_posicion', $ajuste->moneda_posicion ?? 'antes') === 'antes' ? 'selected' : ''); ?>>Antes</option>
                                                        <option value="despues" <?php echo e(old('moneda_posicion', $ajuste->moneda_posicion ?? '') === 'despues' ? 'selected' : ''); ?>>Después</option>
                                                    </select>
                                                    <?php $__errorArgs = ['moneda_posicion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="row">

                                    <div class="col-md-12">
                                        <div class="form-group mb-4">
                                            <label for="logo" class="form-label">Logo <?php if(isset($ajuste) && $ajuste->logo): ?> <?php else: ?><?php endif; ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-image"></i></span>
                                                <input type="file" name="logo" id="logo"
                                                    onchange="mostrarLogo(event)"
                                                    class="form-control <?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    accept="image/*" <?php if(isset($ajuste) && $ajuste->logo): ?> <?php else: ?> required <?php endif; ?>>
                                                <?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                            <center>
                                                <?php if(isset($ajuste) && $ajuste->logo): ?>
                                                    <img src="<?php echo e(asset('storage/' . $ajuste->logo)); ?>"id="preview1"
                                                        style="max-width: 300px; width: 100%; margin-top: 10px;" alt="Logo actual">
                                                <?php else: ?>
                                                <img src="" id="preview1"
                                                    style="max-width: 300px; width: 100%; margin-top: 10px;" alt="Vista previa del logo">
                                                    
                                                <?php endif; ?>
                                            </center>
                                            <img src="" id="preview1"
                                                style="max-width: 300px; width: 100%; margin-top: 10px;" alt="">
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="imagen_login" class="form-label">Imagen login <?php if(isset($ajuste) && $ajuste->imagen_login): ?> <?php else: ?><?php endif; ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-image"></i></span>
                                                <input type="file" name="imagen_login" id="imagen_login"
                                                    onchange="mostrarImagenLogin(event)"
                                                    class="form-control <?php $__errorArgs = ['imagen_login'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                    accept="image/*" <?php if(isset($ajuste) && $ajuste->imagen_login): ?> <?php else: ?> required <?php endif; ?>>
                                                <?php $__errorArgs = ['imagen_login'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </div>
                                            <center>
                                                <?php if(isset($ajuste) && $ajuste->imagen_login): ?>
                                                    <img src="<?php echo e(asset('storage/' . $ajuste->imagen_login)); ?>"id="preview2"
                                                        style="max-width: 300px; width: 100%; margin-top: 10px;" alt="Imagen de login actual">
                                                <?php else: ?>
                                                <img src="" id="preview2"
                                                    style="max-width: 300px; width: 100%; margin-top: 10px;" alt="Vista previa de la imagen de login">
                                                    
                                                <?php endif; ?>
                                            </center>
                                            <img src="" id="preview2"
                                                style="max-width: 300px; width: 100%; margin-top: 10px;" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                    <?php $__currentLoopData = [
                                        ['key' => 'web', 'prefix' => 'web_', 'title' => 'Pago exclusivo de la página web', 'description' => 'Estos datos y códigos QR se usan únicamente en el checkout y en los pedidos creados desde la página web. No modifican los medios de pago de la app GALY.'],
                                        ['key' => 'app', 'prefix' => '', 'title' => 'Pago exclusivo de la app GALY', 'description' => 'La app abre directamente Yape o Interbank/Plin; aquí solo necesitas configurar el número receptor y el titular. No requiere QR y no modifica los medios de pago de la página web.'],
                                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentChannel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <hr class="my-4">
                                        <h5 class="<?php echo e($paymentChannel['key'] === 'web' ? 'text-primary' : 'text-success'); ?> mb-2">
                                            <i class="bi <?php echo e($paymentChannel['key'] === 'web' ? 'bi-globe2' : 'bi-phone'); ?>"></i>
                                            <?php echo e($paymentChannel['title']); ?>

                                        </h5>
                                        <div class="alert alert-<?php echo e($paymentChannel['key'] === 'web' ? 'primary' : 'success'); ?> py-2 small mb-3">
                                            <i class="bi bi-info-circle me-1"></i><?php echo e($paymentChannel['description']); ?>

                                            Configura los datos de este canal por separado; pulsa <strong>Guardar cambios</strong> para aplicar.
                                        </div>
                                        <div class="row g-3">
                                            <?php $__currentLoopData = [
                                                ['key' => 'yape', 'label' => 'Yape', 'color' => 'primary', 'banco' => false],
                                                ['key' => 'bcp', 'label' => 'BCP', 'color' => 'danger', 'banco' => true],
                                                ['key' => 'plin', 'label' => 'Plin', 'color' => 'success', 'banco' => false],
                                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentMethod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $methodKey = $paymentMethod['key'];
                                                    $channelKey = $paymentChannel['key'];
                                                    $esBanco = $paymentMethod['banco'];
                                                    $fieldPrefix = $esBanco
                                                        ? ($channelKey === 'web' ? 'web_bcp' : 'app_bcp')
                                                        : $paymentChannel['prefix'].$methodKey;
                                                    $numberField = $fieldPrefix.($esBanco ? '_cci' : '_numero');
                                                    $holderField = $fieldPrefix.'_titular';
                                                    $cuentaField = $esBanco ? $fieldPrefix.'_cuenta' : null;
                                                    $qrField = $esBanco || $channelKey === 'app' ? null : $fieldPrefix.'_qr';
                                                    $fieldId = $channelKey.'-'.$methodKey;
                                                    $hasSavedQr = $qrField ? filled($ajuste?->{$qrField}) : true;
                                                    $hasNumber = filled($ajuste?->{$numberField});
                                                    $hasHolder = filled($ajuste?->{$holderField});
                                                    $motivoDestino = \App\Models\Ajuste::motivoDeDestinoPago(
                                                        $methodKey,
                                                        $ajuste?->{$numberField},
                                                        $ajuste?->{$holderField},
                                                        $qrField ? $ajuste?->{$qrField} : null,
                                                        $channelKey === 'web'
                                                    );
                                                    $isConfigured = $motivoDestino === null;
                                                    $canalNombre = $channelKey === 'web' ? 'la página web' : 'la app GALY';
                                                    $canalCorto = $channelKey === 'web' ? 'web' : 'app';
                                                    $dondeSeOfrece = $channelKey === 'web' ? 'el checkout' : 'la app';
                                                ?>
                                                <div class="col-12 col-lg-6 col-xxl-4">
                                                    <section class="card h-100 border shadow-sm" aria-labelledby="<?php echo e($fieldId); ?>-payment-title">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                                                <h6 id="<?php echo e($fieldId); ?>-payment-title" class="fw-bold mb-0">
                                                                    <i class="bi bi-<?php echo e($esBanco ? 'bank' : 'phone'); ?> me-1 text-<?php echo e($paymentMethod['color']); ?>"></i>
                                                                    <?php echo e($paymentMethod['label']); ?>

                                                                </h6>
                                                                <span id="<?php echo e($fieldId); ?>-payment-status"
                                                                    class="badge <?php echo e($isConfigured ? 'bg-success' : (($hasNumber && $hasHolder) ? 'bg-danger' : 'bg-warning text-dark')); ?>">
                                                                    <?php if($isConfigured): ?>
                                                                        Activo en <?php echo e($channelKey === 'web' ? 'web' : 'app'); ?>

                                                                    <?php elseif($hasNumber && $hasHolder): ?>
                                                                        Bloqueado por seguridad
                                                                    <?php else: ?>
                                                                        Pendiente de configurar
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            <?php if(! $isConfigured && $hasNumber && $hasHolder): ?>
                                                                <div class="alert alert-danger py-2 small mb-3" role="alert">
                                                                    <i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i>
                                                                    Este método no se ofrece a los clientes porque <?php echo e($motivoDestino); ?>.
                                                                    Nadie debe transferir a un destino que no sea el banco real de la tienda.
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="row g-3">
                                                                <div class="col-12">
                                                                    <label for="<?php echo e($numberField); ?>" class="form-label"><?php echo e($esBanco ? 'CCI BCP' : 'Número receptor'); ?></label>
                                                                    <input type="<?php echo e($esBanco ? 'text' : 'tel'); ?>"
                                                                        inputmode="<?php echo e($esBanco ? 'numeric' : 'tel'); ?>"
                                                                        autocomplete="<?php echo e($esBanco ? 'off' : 'tel'); ?>"
                                                                        <?php if($esBanco): ?> maxlength="30" <?php endif; ?>
                                                                        name="<?php echo e($numberField); ?>" id="<?php echo e($numberField); ?>"
                                                                        class="form-control <?php $__errorArgs = [$numberField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                                        value="<?php echo e(old($numberField, $ajuste->{$numberField} ?? '')); ?>"
                                                                        placeholder="<?php echo e($esBanco ? 'CCI de la cuenta receptora' : 'Número asociado a '.$paymentMethod['label']); ?>"
                                                                        oninput="actualizarEstadoQrPago('<?php echo e($channelKey); ?>', '<?php echo e($methodKey); ?>')">
                                                                    <?php if($esBanco): ?>
                                                                        <div class="form-text">Son 20 dígitos, incluidos los ceros del inicio. Es exactamente el número que tu app muestra junto a «CCI:», no el número impreso de tu tarjeta.</div>
                                                                    <?php endif; ?>
                                                                    <?php $__errorArgs = [$numberField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                                </div>
                                                                <?php if($cuentaField): ?>
                                                                    <div class="col-12">
                                                                        <label for="<?php echo e($cuentaField); ?>" class="form-label">Número de cuenta (opcional)</label>
                                                                        <input type="text" inputmode="numeric" autocomplete="off" maxlength="30"
                                                                            name="<?php echo e($cuentaField); ?>" id="<?php echo e($cuentaField); ?>"
                                                                            class="form-control <?php $__errorArgs = [$cuentaField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                                            value="<?php echo e(old($cuentaField, $ajuste->{$cuentaField} ?? '')); ?>"
                                                                            placeholder="Cuenta BCP">
                                                                        <div class="form-text">El número de cuenta que aparece <em>encima</em> del CCI en tu app (ojo: no son lo mismo). Es opcional; <strong>nunca escribas aquí el número impreso de tu tarjeta</strong>: el cliente lo vería como destino de pago.</div>
                                                                        <?php $__errorArgs = [$cuentaField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="col-12">
                                                                    <label for="<?php echo e($holderField); ?>" class="form-label">Titular</label>
                                                                    <input type="text" name="<?php echo e($holderField); ?>" id="<?php echo e($holderField); ?>"
                                                                        class="form-control <?php $__errorArgs = [$holderField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                                        value="<?php echo e(old($holderField, $ajuste->{$holderField} ?? '')); ?>"
                                                                        placeholder="Nombre del titular"
                                                                        oninput="actualizarEstadoQrPago('<?php echo e($channelKey); ?>', '<?php echo e($methodKey); ?>')">
                                                                    <?php $__errorArgs = [$holderField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                                </div>
                                                                <?php if($qrField): ?>
                                                                    <div class="col-12">
                                                                    <label for="<?php echo e($qrField); ?>" class="form-label">
                                                                        QR oficial <?php echo e($hasSavedQr ? '(guardado; puedes reemplazarlo)' : '(requerido para activar)'); ?>

                                                                    </label>
                                                                    <input type="file" name="<?php echo e($qrField); ?>" id="<?php echo e($qrField); ?>"
                                                                        data-has-saved-qr="<?php echo e($hasSavedQr ? '1' : '0'); ?>"
                                                                        data-saved-src="<?php echo e($hasSavedQr ? url('/storage/'.ltrim($ajuste->{$qrField}, '/')) : ''); ?>"
                                                                        data-required-when="<?php echo e($esBanco ? 'CCI' : 'número'); ?>"
                                                                        onchange="mostrarQrPago(event, '<?php echo e($channelKey); ?>', '<?php echo e($methodKey); ?>')"
                                                                        class="form-control <?php $__errorArgs = [$qrField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                                        accept="image/jpeg,image/png,image/webp"
                                                                        <?php if($hasNumber && ! $hasSavedQr): echo 'required'; endif; ?>>
                                                                    <small class="form-text text-muted">PNG, JPG o WEBP. Máximo 3 MB.</small>
                                                                    <?php $__errorArgs = [$qrField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                                        <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                                </div>
                                                                <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                                                                    <img id="<?php echo e($fieldId); ?>-qr-preview"
                                                                        src="<?php echo e($hasSavedQr ? url('/storage/'.ltrim($ajuste->{$qrField}, '/')) : ''); ?>"
                                                                        alt="Vista previa del QR oficial de <?php echo e($paymentMethod['label']); ?> para <?php echo e($canalNombre); ?>"
                                                                        class="img-thumbnail <?php echo e($hasSavedQr ? '' : 'd-none'); ?>"
                                                                        style="width: 150px; height: 150px; object-fit: contain">
                                                                    <span id="<?php echo e($fieldId); ?>-qr-help" class="small <?php echo e($hasNumber && ! $hasSavedQr || $hasSavedQr && ! $hasNumber ? 'text-warning' : 'text-muted'); ?>">
                                                                        <?php if($hasNumber && $hasSavedQr): ?>
                                                                            Configuración activa solamente en <?php echo e($canalNombre); ?>.
                                                                        <?php elseif($hasNumber && ! $hasSavedQr): ?>
                                                                            Este método no aparecerá en <?php echo e($dondeSeOfrece); ?> hasta guardar su QR oficial.
                                                                        <?php elseif(! $hasNumber && $hasSavedQr): ?>
                                                                            El QR ya está guardado: faltan <?php echo e($esBanco ? 'la CCI' : 'el número receptor'); ?> y el titular para que aparezca en <?php echo e($dondeSeOfrece); ?>.
                                                                        <?php else: ?>
                                                                            Completa <?php echo e($esBanco ? 'la CCI' : 'el número receptor'); ?>, el titular y el QR para ofrecer este método en <?php echo e($dondeSeOfrece); ?>.
                                                                        <?php endif; ?>
                                                                    </span>
                                                                </div>
                                                                <?php else: ?>
                                                                    <div class="col-12">
                                                                        <span id="<?php echo e($fieldId); ?>-qr-help" class="small <?php echo e($isConfigured ? 'text-success' : 'text-warning'); ?>">
                                                                            <?php if($isConfigured): ?>
                                                                                Método activo en <?php echo e($canalNombre); ?>.
                                                                                <?php if($esBanco): ?>
                                                                                    El cliente verá la CCI y el titular en el checkout.
                                                                                <?php else: ?>
                                                                                    Al tocar el botón se abrirá la app bancaria correspondiente.
                                                                                <?php endif; ?>
                                                                            <?php elseif($hasNumber && $hasHolder): ?>
                                                                                Revisa el aviso rojo: los datos están puestos pero
                                                                                no son un destino real de la tienda.
                                                                            <?php else: ?>
                                                                                Este método no aparecerá en <?php echo e($dondeSeOfrece); ?> hasta completar
                                                                                <?php echo e($esBanco ? 'la CCI y el titular' : 'el número y el titular'); ?>.
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </section>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>

                                        <?php if($paymentChannel['key'] === 'web'): ?>
                                            <?php
                                                $mercadoPago = app(\App\Services\MercadoPagoService::class);
                                                $pasarelista = $mercadoPago->habilitado();
                                            ?>
                                            <div class="border rounded-3 p-3 mt-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="web_mercadopago_activa" name="web_mercadopago_activa" value="1" <?php if($ajuste?->web_mercadopago_activa): echo 'checked'; endif; ?>>
                                                    <label class="form-check-label fw-semibold" for="web_mercadopago_activa">
                                                        <i class="bi bi-credit-card-2-front text-primary me-1"></i>Ofrecer pago con tarjeta Visa
                                                    </label>
                                                </div>
                                                <p class="small text-muted mb-2 mt-2">
                                                    El cliente completa el pago en Mercado Pago y el pedido se marca como pagado apenas la tarjeta es aprobada,
                                                    sin subir comprobantes. La tienda no ve ni guarda los datos de la tarjeta.
                                                </p>
                                                <?php if($mercadoPago->rechazoReciente()): ?>
                                                    
                                                    <span class="badge bg-warning text-dark">Tarjeta en pausa</span>
                                                    <span class="small text-muted">
                                                        En el carrito se ve gris con el aviso «Próximamente», sin que el
                                                        cliente pueda elegirla, aunque esta casilla siga marcada.
                                                        Nadie puede pagar mal por este motivo.
                                                        <?php if($mercadoPago->bloqueoDefinitivo()): ?>
                                                            Vuelve a estar disponible cuando
                                                        <?php else: ?>
                                                            Vuelve sola media hora después de dejar de fallar, o cuando
                                                        <?php endif; ?>
                                                        <code>php artisan mercadopago:verificar --probar</code> confirme un
                                                        Access Token de tu cuenta.
                                                    </span>
                                                <?php elseif($pasarelista && ! $mercadoPago->credencialVerificada()): ?>
                                                    
                                                    <span class="badge bg-warning text-dark">Credencial sin comprobar</span>
                                                    <span class="small text-muted">
                                                        En el carrito ya se ve gris con el aviso «Próximamente», pero
                                                        nadie puede pagar con ella todavía. Para que pase a activa,
                                                        pega tu Access Token con
                                                        <code>.\mercadopago-token.ps1</code> o corre en la terminal
                                                        <code>php artisan mercadopago:verificar --probar</code>.
                                                        Si Mercado Pago acepta el token, la prueba deja constancia en
                                                        la base y la opción aparece sola; si lo rechaza, aquí verás
                                                        «Tarjeta en pausa» con el motivo.
                                                    </span>
                                                <?php elseif($pasarelista): ?>
                                                    <span class="badge bg-success">Pasarela configurada</span>
                                                    <span class="small text-success">Marca esta casilla para mostrar «Tarjeta Visa» en el checkout.</span>
                                                <?php else: ?>
                                                    <?php
                                                        $credencialesFaltantes = [];
                                                        if (config('services.mercadopago.enabled') !== true) {
                                                            $credencialesFaltantes[] = 'MERCADOPAGO_ENABLED=true';
                                                        }
                                                        if (!$mercadoPago->tokenVerosimil()) {
                                                            $credencialesFaltantes[] = 'MERCADOPAGO_ACCESS_TOKEN=…';
                                                        }
                                                    ?>
                                                    <span class="badge bg-warning text-dark">Faltan credenciales</span>
                                                    <span class="small text-warning">
                                                        Escribe <?php echo e(implode(' y ', $credencialesFaltantes)); ?> en el archivo .env
                                                        (está en la carpeta raíz del proyecto, es un archivo de texto, no un campo de este panel).
                                                        Sin ellas esta opción no aparecerá en el checkout, aunque la actives aquí.
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                    <hr class="my-4">
                                    <h5 class="text-primary mb-3"><i class="bi bi-star-fill"></i> Configuración del Sistema de Reseñas</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label class="form-label d-block fw-bold">Moderación de Reseñas</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="review_requiere_aprobacion" id="review_requiere_aprobacion" value="1" 
                                                        <?php echo e(old('review_requiere_aprobacion', $ajuste->review_requiere_aprobacion ?? true) ? 'checked' : ''); ?>>
                                                    <label class="form-check-label fw-semibold" for="review_requiere_aprobacion">Las reseñas requieren aprobación del administrador</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label class="form-label d-block fw-bold">Permitir Imágenes</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="review_permitir_imagenes" id="review_permitir_imagenes" value="1" 
                                                        <?php echo e(old('review_permitir_imagenes', $ajuste->review_permitir_imagenes ?? true) ? 'checked' : ''); ?>>
                                                    <label class="form-check-label fw-semibold" for="review_permitir_imagenes">Permitir subir imágenes en reseñas</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label class="form-label d-block fw-bold">Eliminación por Cliente</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="review_permitir_eliminacion" id="review_permitir_eliminacion" value="1" 
                                                        <?php echo e(old('review_permitir_eliminacion', $ajuste->review_permitir_eliminacion ?? true) ? 'checked' : ''); ?>>
                                                    <label class="form-check-label fw-semibold" for="review_permitir_eliminacion">Los clientes pueden eliminar sus propias reseñas</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="review_max_imagenes" class="form-label fw-bold">Imágenes Máximas por Reseña (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-images"></i></span>
                                                    <input type="number" name="review_max_imagenes" id="review_max_imagenes" class="form-control <?php $__errorArgs = ['review_max_imagenes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('review_max_imagenes', $ajuste->review_max_imagenes ?? 5)); ?>" min="1" max="10" required>
                                                    <?php $__errorArgs = ['review_max_imagenes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="review_longitud_maxima" class="form-label fw-bold">Longitud Máxima de Comentario (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                                                    <input type="number" name="review_longitud_maxima" id="review_longitud_maxima" class="form-control <?php $__errorArgs = ['review_longitud_maxima'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('review_longitud_maxima', $ajuste->review_longitud_maxima ?? 1000)); ?>" min="50" max="5000" required>
                                                    <?php $__errorArgs = ['review_longitud_maxima'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="review_tiempo_edicion_dias" class="form-label fw-bold">Plazo de Edición (Días) (*)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-clock-history"></i></span>
                                                    <input type="number" name="review_tiempo_edicion_dias" id="review_tiempo_edicion_dias" class="form-control <?php $__errorArgs = ['review_tiempo_edicion_dias'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                        value="<?php echo e(old('review_tiempo_edicion_dias', $ajuste->review_tiempo_edicion_dias ?? 30)); ?>" min="1" max="365" required>
                                                    <?php $__errorArgs = ['review_tiempo_edicion_dias'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                        <div class="invalid-feedback d-block"><strong><?php echo e($message); ?></strong></div>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                            
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i>
                                            Guardar cambios</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const mostrarLogo = e => {
            if (e.target.files[0]) {
                document.getElementById('preview1').src = URL.createObjectURL(e.target.files[0]);
            }
        }

        const mostrarImagenLogin = e => {
            if (e.target.files[0]) {
                document.getElementById('preview2').src = URL.createObjectURL(e.target.files[0]);
            }
        }

        const mostrarQrPago = (event, channel, method) => {
            const input = event.target;
            const file = input.files[0];
            const fieldId = `${channel}-${method}`;
            const preview = document.getElementById(`${fieldId}-qr-preview`);

            if (preview) {
                if (preview.dataset.objectUrl) {
                    URL.revokeObjectURL(preview.dataset.objectUrl);
                    delete preview.dataset.objectUrl;
                }

                const imageUrl = file ? URL.createObjectURL(file) : input.dataset.savedSrc;
                preview.src = imageUrl;
                preview.classList.toggle('d-none', !imageUrl);
                if (file) preview.dataset.objectUrl = imageUrl;
            }

            actualizarEstadoQrPago(channel, method);
        }

        const metodosDePago = {
            yape: { numero: '_numero', nombreNumero: 'el número receptor', banco: false },
            bcp: { numero: '_cci', nombreNumero: 'la CCI', banco: true },
            plin: { numero: '_numero', nombreNumero: 'el número receptor', banco: false },
        };

        const camposDePago = (channel, method) => {
            const config = metodosDePago[method];
            const base = config.banco
                ? (channel === 'web' ? 'web_bcp' : 'app_bcp')
                : `${channel === 'web' ? 'web_' : ''}${method}`;
            const fieldId = `${channel}-${method}`;

            return {
                config,
                number: document.getElementById(`${base}${config.numero}`),
                holder: document.getElementById(`${base}_titular`),
                qr: config.banco || channel === 'app' ? null : document.getElementById(`${base}_qr`),
                status: document.getElementById(`${fieldId}-payment-status`),
                help: document.getElementById(`${fieldId}-qr-help`),
            };
        };

        const actualizarEstadoQrPago = (channel, method) => {
            const { config, number, holder, qr, status, help } = camposDePago(channel, method);
            if (!number || !holder || !status || !help) return;

            const tieneNumero = number.value.trim() !== '';
            const tieneTitular = holder.value.trim() !== '';
            const qrGuardado = qr ? qr.dataset.hasSavedQr === '1' : true;
            const qrSeleccionado = Boolean(qr && qr.files.length > 0);
            const tieneQr = qrGuardado || qrSeleccionado;
            const donde = channel === 'web' ? 'el checkout' : 'la app';
            const canalNombre = channel === 'web' ? 'la página web' : 'la app GALY';

            if (qr) qr.required = tieneNumero && !tieneQr;

            status.className = 'badge';
            help.className = 'small';

            if (qr && qrSeleccionado && !qrGuardado && tieneNumero && tieneTitular) {
                status.classList.add('bg-primary');
                status.textContent = 'Listo al guardar';
                help.classList.add('text-primary');
                help.textContent = 'Vista previa seleccionada. Pulsa «Guardar cambios» para activar el método.';

                return;
            }

            if (tieneNumero && tieneTitular && tieneQr) {
                status.classList.add('bg-success');
                status.textContent = `Activo en ${channel}`;
                help.classList.add('text-success');
                help.textContent = qr
                    ? `Datos completos. El método se ofrece solamente en ${canalNombre}.`
                    : (config.banco
                        ? `El cliente verá ${config.nombreNumero} y el titular al pagar.`
                        : 'Al tocar el botón de pago se abrirá la app bancaria correspondiente.');

                return;
            }

            status.classList.add('bg-warning', 'text-dark');
            help.classList.add('text-warning');

            if (!tieneNumero && !tieneTitular) {
                status.textContent = `Faltan ${config.nombreNumero} y el titular`;
                help.textContent = `Este método no aparecerá en ${donde} hasta completar ${config.nombreNumero}, el titular${qr ? ' y el QR oficial' : ''}.`;
            } else if (!tieneNumero) {
                status.textContent = `Falta ${config.nombreNumero}`;
                help.textContent = qr && qrGuardado
                    ? `El QR ya está guardado: solo falta ${config.nombreNumero} para que aparezca en ${donde}.`
                    : `Este método no aparecerá en ${donde} hasta completar ${config.nombreNumero}${qr ? ' y su QR oficial' : ''}.`;
            } else if (!tieneTitular) {
                status.textContent = 'Falta el titular';
                help.textContent = `Este método no aparecerá en ${donde} hasta completar el titular.`;
            } else {
                status.textContent = 'Falta el QR';
                help.textContent = `Este método no aparecerá en ${donde} hasta guardar su QR oficial.`;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            ['web', 'app'].forEach(channel => {
                Object.keys(metodosDePago).forEach(method => actualizarEstadoQrPago(channel, method));
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\ecommerce (4)\ecommerce\resources\views/admin/ajustes/index.blade.php ENDPATH**/ ?>