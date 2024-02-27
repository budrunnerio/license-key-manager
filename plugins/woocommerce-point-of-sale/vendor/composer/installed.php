<?php return array(
    'root' => array(
        'name' => 'woocommerce/woocommerce-point-of-sale',
        'pretty_version' => 'dev-develop',
        'version' => 'dev-develop',
        'reference' => 'ef9c1f6e024689dcfd4186957eb10377596ad94f',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.10.0',
            'version' => '1.10.0.0',
            'reference' => '1a0357fccad9d1cc1ea0c9a05b8847fbccccb78d',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'mexitek/phpcolors' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'e10e44f67632096072237ff0822674a442d4bb50',
            'type' => 'library',
            'install_path' => __DIR__ . '/../mexitek/phpcolors',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'stripe/stripe-php' => array(
            'pretty_version' => 'v9.9.0',
            'version' => '9.9.0.0',
            'reference' => '479b5c2136fde0debb93d290ceaf20dd161c358f',
            'type' => 'library',
            'install_path' => __DIR__ . '/../stripe/stripe-php',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'woocommerce/woocommerce-point-of-sale' => array(
            'pretty_version' => 'dev-develop',
            'version' => 'dev-develop',
            'reference' => 'ef9c1f6e024689dcfd4186957eb10377596ad94f',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
