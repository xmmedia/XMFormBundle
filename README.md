# XMFormBundle
A few helpers to make processing (creating, saving, errors, etc) forms simpler.

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

Adding the following will make the call to the service shorter:

```
form_handler: '@xm_form.handler'
```

## Usage

### Get the form handler

```
$formHandler = $this->get('form_handler');
```

### Create the form

```
$form = $formHandler->getForm(
    EntityFormType::class,
    $entity,
    $request
);
```

### Process form & save entity

```
if ($formHandler->processForm($form, $entity, '[entity name]')) {
    // entity valid and saved successfully, redirect
}
```

### Retrieve validation errors/messages as an array

This is useful when passing the validation messages to JS through JSON.

```
FormErrors::flatten($form)
```

This will return an array of the errors in the format of:

```
array(
    0 => array(
        'Global Error 1',
    ),
    'field_name' => array(
        'Field Error 1',
        'Field Error 2',
        ...
    ),
    ...
)
```