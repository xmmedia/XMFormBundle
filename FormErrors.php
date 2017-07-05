<?php

namespace XM\FormBundle;

use Symfony\Component\Form\FormInterface;

class FormErrors
{
    /**
     * Creates a flat-ish array of the errors on the form,
     * keyed by their field name.
     * May contain nested arrays of errors if the form has child forms
     * and those have children.
     * If there are global form errors, they will numerically keyed.
     *
     * @param FormInterface $form
     *
     * @return array
     */
    public static function flatten(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $child) {
            if ($child instanceof FormInterface) {
                if ($childErrors = self::flatten($child)) {
                    $errors[$child->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}