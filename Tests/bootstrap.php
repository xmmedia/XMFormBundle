<?php

/*
 * This file is part of the XMFormBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!($loader = @include __DIR__.'/../vendor/autoload.php')) {
    echo <<<'EOT'
You need to install the bundle dependencies using Composer.
https://getcomposer.org/download/
EOT;
    exit(1);
}