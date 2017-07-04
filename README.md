# XMFormBunlde
[@todo]

## Installation

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ php composer.phar require xm/form-bundle
```

This command requires [Composer](https://getcomposer.org/download/).

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new XM\FormBundle\XMFormBundle(),
        );

        // ...
    }
}
```

### Step 3: Add Service Alias

[@todo ?]

Adding the following will make the call to the service shorter:

```
flash_handler: '@xm_flash.handler'
```

## Usage

[@todo]