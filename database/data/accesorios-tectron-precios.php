<?php

/*
| Precios del inventario físico de Tectron, en soles (S/).
|
| Van aparte de accesorios-tectron.php para poder corregir un precio sin
| tocar la descripción del producto. La clave es la columna "f" (fila) del
| archivo de inventario.
|
|   pc = precio de compra (lo que cuesta reponerlo)
|   pv = precio de venta al público
|
| Son precios referenciales de mostrador: ajústalos en Admin → Productos →
| Editar cuando tengas la lista definitiva del área de ventas.
*/

return [
    1 => ['pc' => 15.00, 'pv' => 25.00],   // Protector de teclado 15.6
    2 => ['pc' => 7.00, 'pv' => 12.00],    // Protector de pantalla 15.6
    3 => ['pc' => 9.00, 'pv' => 15.00],    // Protector de pantalla 16
    4 => ['pc' => 6.00, 'pv' => 10.00],    // Skin
    5 => ['pc' => 9.00, 'pv' => 15.00],    // Pad mouse con almohadilla
    6 => ['pc' => 32.00, 'pv' => 50.00],   // Funda 13
    7 => ['pc' => 38.00, 'pv' => 60.00],   // Funda 14
    8 => ['pc' => 33.00, 'pv' => 50.00],   // Funda 15.6
    9 => ['pc' => 85.00, 'pv' => 140.00],  // Mochila
    10 => ['pc' => 12.00, 'pv' => 20.00],  // Kit de limpieza

    11 => ['pc' => 35.00, 'pv' => 55.00],  // Mouse Enkore
    12 => ['pc' => 28.00, 'pv' => 45.00],  // Mouse Genius DX-101
    13 => ['pc' => 30.00, 'pv' => 48.00],  // Mouse Genius DX-110
    14 => ['pc' => 45.00, 'pv' => 75.00],  // Mouse Logitech M170
    15 => ['pc' => 38.00, 'pv' => 60.00],  // Mouse Seisa azul DN-N614
    16 => ['pc' => 38.00, 'pv' => 60.00],  // Mouse Seisa blanco DN-N615
    17 => ['pc' => 38.00, 'pv' => 60.00],  // Mouse Seisa negro DN-N616
    18 => ['pc' => 35.00, 'pv' => 55.00],  // Mouse Seisa azul DN-N613
    19 => ['pc' => 35.00, 'pv' => 55.00],  // Mouse Seisa blanco DN-N614
    20 => ['pc' => 35.00, 'pv' => 55.00],  // Mouse Seisa negro DN-N615
    21 => ['pc' => 45.00, 'pv' => 75.00],  // Mouse Genius NX-7000
    22 => ['pc' => 50.00, 'pv' => 80.00],  // Mouse Genius NX-7009
    23 => ['pc' => 55.00, 'pv' => 90.00],  // Mouse Acer gamer
    24 => ['pc' => 55.00, 'pv' => 90.00],  // Mouse Halion Assassin

    25 => ['pc' => 8.00, 'pv' => 15.00],   // Pasta térmica
    26 => ['pc' => 20.00, 'pv' => 35.00],  // Case de disco duro
    27 => ['pc' => 8.00, 'pv' => 15.00],   // Cable HDMI

    28 => ['pc' => 60.00, 'pv' => 95.00],  // Tinta Brother original magenta
    29 => ['pc' => 55.00, 'pv' => 90.00],  // Tinta Canon 11 original yellow
    30 => ['pc' => 55.00, 'pv' => 90.00],  // Tinta Canon 11 original magenta
    31 => ['pc' => 55.00, 'pv' => 90.00],  // Tinta Canon 11 original cyan
    32 => ['pc' => 50.00, 'pv' => 85.00],  // Tinta Canon 10 original negro
    33 => ['pc' => 45.00, 'pv' => 75.00],  // Caja de mantenimiento Canon MC-G02
    34 => ['pc' => 45.00, 'pv' => 75.00],  // Adaptador WiFi TP-Link
    35 => ['pc' => 120.00, 'pv' => 190.00], // Batería HP HT03XL
    36 => ['pc' => 45.00, 'pv' => 75.00],  // Teclado Halion HA-K394
    37 => ['pc' => 45.00, 'pv' => 75.00],  // Teclado Halion HA-K392
    38 => ['pc' => 60.00, 'pv' => 95.00],  // Combo Halion HA-K439C
    39 => ['pc' => 85.00, 'pv' => 135.00], // Combo Icecool blanco
    40 => ['pc' => 85.00, 'pv' => 135.00], // Combo Icecool negro
    41 => ['pc' => 90.00, 'pv' => 145.00], // Combo gamer Harrier
    42 => ['pc' => 55.00, 'pv' => 90.00],  // Cooler Cybercool HA-71
    43 => ['pc' => 55.00, 'pv' => 90.00],  // Cooler Cybercool HA-K4
    44 => ['pc' => 15.00, 'pv' => 25.00],  // Adaptador DVI a HDMI
    45 => ['pc' => 12.00, 'pv' => 20.00],  // USB LED
    46 => ['pc' => 25.00, 'pv' => 40.00],  // Soporte plegable

    47 => ['pc' => 65.00, 'pv' => 100.00], // Cargador HP punta celeste
    48 => ['pc' => 65.00, 'pv' => 100.00], // Cargador HP punta aguja
    49 => ['pc' => 70.00, 'pv' => 110.00], // Cargador Toshiba
    50 => ['pc' => 65.00, 'pv' => 100.00], // Cargador Acer amarilla
    51 => ['pc' => 70.00, 'pv' => 110.00], // Cargador Acer delgada
    52 => ['pc' => 70.00, 'pv' => 110.00], // Cargador Asus delgada
    53 => ['pc' => 75.00, 'pv' => 115.00], // Cargador Asus Vivobook
    54 => ['pc' => 65.00, 'pv' => 100.00], // Cargador Asus normal
    55 => ['pc' => 75.00, 'pv' => 115.00], // Cargador Lenovo USB
    56 => ['pc' => 85.00, 'pv' => 130.00], // Cargador Lenovo tipo C
    57 => ['pc' => 70.00, 'pv' => 110.00], // Cargador Lenovo delgada
    58 => ['pc' => 110.00, 'pv' => 170.00], // Cargador Asus original
    59 => ['pc' => 110.00, 'pv' => 170.00], // Cargador Lenovo original

    60 => ['pc' => 25.00, 'pv' => 40.00],  // Tinta litro Jet Life UV magenta
    61 => ['pc' => 25.00, 'pv' => 40.00],  // Tinta litro Jet Life UV yellow
    62 => ['pc' => 25.00, 'pv' => 40.00],  // Tinta litro Jet Life UV cyan
    63 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 664 magenta
    64 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 664 cyan
    65 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 664 yellow
    66 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 664 black
    67 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 190 magenta
    68 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 190 cyan
    69 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 190 yellow
    70 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 11 magenta
    71 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 11 cyan
    72 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 11 yellow
    73 => ['pc' => 15.00, 'pv' => 25.00],  // Jet Life 11 black
    74 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 544 magenta
    75 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 544 cyan
    76 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 544 yellow
    77 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 544 black
    78 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 504 magenta
    79 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 504 cyan
    80 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 504 yellow
    81 => ['pc' => 18.00, 'pv' => 30.00],  // Jet Life 504 black

    82 => ['pc' => 85.00, 'pv' => 130.00], // Fuente Avatec 600 W
    83 => ['pc' => 70.00, 'pv' => 110.00], // Fuente Avatec 450 W reales
    84 => ['pc' => 50.00, 'pv' => 80.00],  // Fuente Teros 250 W reales
    85 => ['pc' => 190.00, 'pv' => 280.00], // Fuente Forgame AK700
    86 => ['pc' => 150.00, 'pv' => 230.00], // Memoria RAM DDR5 16 GB

    87 => ['pc' => 35.00, 'pv' => 60.00],  // Parlante Halion HA-263
    88 => ['pc' => 35.00, 'pv' => 60.00],  // Parlante Halion rojo HA-228
    89 => ['pc' => 35.00, 'pv' => 60.00],  // Parlante Halion azul HA-228
    90 => ['pc' => 40.00, 'pv' => 65.00],  // Parlante Halion HA-S270

    91 => ['pc' => 25.00, 'pv' => 45.00],  // RJ45 Cat 5 caja
    92 => ['pc' => 30.00, 'pv' => 50.00],  // RJ45 Cat 6 caja
    93 => ['pc' => 35.00, 'pv' => 55.00],  // Cable Dixon Cat 5
    94 => ['pc' => 40.00, 'pv' => 65.00],  // Cable Dixon Cat 6
    95 => ['pc' => 8.00, 'pv' => 15.00],   // Cable de datos impresora
    96 => ['pc' => 10.00, 'pv' => 18.00],  // Cable VGA
    97 => ['pc' => 12.00, 'pv' => 20.00],  // Cable DVI
    98 => ['pc' => 15.00, 'pv' => 25.00],  // Cooler con luces
    99 => ['pc' => 10.00, 'pv' => 18.00],  // Cooler pequeño Molex

    100 => ['pc' => 10.00, 'pv' => 18.00], // Cable de poder PC
    101 => ['pc' => 10.00, 'pv' => 18.00], // Cable trébol
    102 => ['pc' => 8.00, 'pv' => 15.00],  // Cable tipo 8
    103 => ['pc' => 30.00, 'pv' => 50.00], // Goma de arrastre Canon
    104 => ['pc' => 40.00, 'pv' => 65.00], // Goma de arrastre Epson completa
    105 => ['pc' => 25.00, 'pv' => 40.00], // Goma de arrastre Epson sola

    106 => ['pc' => 35.00, 'pv' => 55.00], // Almohadilla Epson L210
    107 => ['pc' => 35.00, 'pv' => 55.00], // Almohadilla Epson 5590
    108 => ['pc' => 35.00, 'pv' => 55.00], // Almohadilla Epson 3110
    109 => ['pc' => 35.00, 'pv' => 55.00], // Almohadilla Epson 4160

    110 => ['pc' => 12.00, 'pv' => 20.00], // Pad mouse bordado
    111 => ['pc' => 90.00, 'pv' => 140.00], // Antivirus Panda 3 dispositivos
    112 => ['pc' => 45.00, 'pv' => 75.00], // Antivirus Panda 1 dispositivo
];
