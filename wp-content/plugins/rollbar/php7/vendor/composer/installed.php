<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'rollbar/rollbar-php-wordpress',
        'dev' => true,
    ),
    'versions' => array(
        'michelf/php-markdown' => array(
            'pretty_version' => '1.9.1',
            'version' => '1.9.1.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../michelf/php-markdown',
            'aliases' => array(),
            'reference' => '5024d623c1a057dcd2d076d25b7d270a1d0d55f3',
            'dev_requirement' => false,
        ),
        'monolog/monolog' => array(
            'pretty_version' => '1.27.1',
            'version' => '1.27.1.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../monolog/monolog',
            'aliases' => array(),
            'reference' => '904713c5929655dc9b97288b69cfeedad610c9a1',
            'dev_requirement' => false,
        ),
        'psr/log' => array(
            'pretty_version' => '1.1.4',
            'version' => '1.1.4.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/log',
            'aliases' => array(),
            'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11',
            'dev_requirement' => false,
        ),
        'psr/log-implementation' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '1.0.0',
            ),
        ),
        'rollbar/rollbar' => array(
            'pretty_version' => 'v1.8.1',
            'version' => '1.8.1.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../rollbar/rollbar',
            'aliases' => array(),
            'reference' => '8a57ad9574d85bd818eaedfc8049fdcb16795f31',
            'dev_requirement' => false,
        ),
        'rollbar/rollbar-php-wordpress' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);