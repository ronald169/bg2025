<?php
// config/tinymce.php

return [
    'config' => [
        'language' => app()->getLocale() === 'de' ? 'de' : (app()->getLocale() === 'fr' ? 'fr_FR' : 'en'),
        'plugins' => 'accordion advlist anchor autolink autoresize codesample directionality emoticons fullscreen help image insertdatetime link lists media preview quickbars save searchreplace table visualblocks visualchars wordcount',
        'toolbar' => 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | accordion accordionremove | link image media table | codesample | fullscreen preview | help',
        'toolbar_sticky' => true,
        'min_height' => 500,
        'max_height' => 800,
        'resize' => true,
        'branding' => false,
        'license_key' => 'gpl',
        'valid_elements' => '*[*]',
        'relative_urls' => false,
        'remove_script_host' => true,
        'convert_urls' => true,
        'image_title' => true,
        'image_description' => true,
        'image_caption' => true,
        'quickbars_selection_toolbar' => 'bold italic underline | formatselect | bullist numlist | link',
        'quickbars_insert_toolbar' => 'quickimage quicktable',
    ],
];
