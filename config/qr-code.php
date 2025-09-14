<?php

return [
    'writer' => 'png',
    'size' => 200,
    'margin' => 0,
    'round_block_size' => true,
    'error_correction_level' => 'medium',
    'foreground_color' => [
        'r' => 0,
        'g' => 0,
        'b' => 0,
        'a' => 0,
    ],
    'background_color' => [
        'r' => 255,
        'g' => 255,
        'b' => 255,
        'a' => 0,
    ],
    // Use GD instead of Imagick
    'writer_options' => [
        'exclude_xml_declaration' => true,
    ],
];
