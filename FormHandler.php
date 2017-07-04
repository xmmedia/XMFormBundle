<?php

namespace XM\FormBundle;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use XM\FlashBundle\FlashHandler;

class FormHandler
{
    private $formFactory;
    private $em;
    private $router;
    private $flashHandler;

    public function __construct(
        FormFactoryInterface $formFactory,
        ObjectManager $em,
        Router $router,
        FlashHandler $flashHandler
    ) {
        $this->formFactory = $formFactory;
        $this->em = $em;
        $this->router = $router;
        $this->flashHandler = $flashHandler;
    }

    /**
     * Creates the form, setting the action and method
     * and then handles the request.
     *
     * @param string  $formClass The form class
     * @param object  $entity    The entity
     * @param Request $request   The current request
     * @param array   $options   Array of options
     *
     * @return Form|FormInterface
     */
    public function getForm(
        $formClass,
        $entity,
        Request $request,
        array $options = []
    ) {
        $formOptions = $this->buildFormOptions($entity, $request, $options);

        $form = $this->createForm($formClass, $entity, $formOptions);
        $form->handleRequest($request);

        return $form;
    }

    /**
     * Compiles the options for passing to the create form.
     *
     * @param object  $entity  The entity
     * @param Request $request The current request
     * @param array   $options Array of options
     *                         
     * @return array
     */
    public function buildFormOptions(
        $entity,
        Request $request,
        array $options = []
    ) {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'action'            => null,
            'method'            => 'POST',
            'validation_groups' => null,
        ]);

        $newEntity = (null === $entity->getId());

        if (!array_key_exists('method', $options)) {
            $options['method'] = $newEntity ? 'POST' : 'PUT';
        }

        if (!array_key_exists('action', $options)) {
            $route = $request->attributes->get('_route');

            if ($newEntity) {
                $options['action'] = $this->generateUrl($route);
            } else {
                $options['action'] = $this->generateUrl(
                    $route,
                    ['id' => $entity->getId()]
                );
            }
        }

        return $resolver->resolve($options);
    }

    /**
     * Processes the form, including checking if the input is valid.
     * Also persists the entity if it's a new entity and flushes the em.
     * Adds the appropriate flash messages (created, updated or invalid).
     * Returns true if everything has been saved.
     * Returns false if there was an error and the form should be displayed again.
     *
     * @param Form $form The form.
     * @param object $entity The entity
     * @param string $userEntityName The name of entity to use in flash messages
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     *
     * @return bool
     */
    public function processForm(
        Form $form,
        $entity,
        $userEntityName,
        ObjectManager $em = null
    ) {
        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $em) {
                $em = $this->em;
            }

            $newEntity = !$em->contains($entity);

            if ($newEntity) {
                $em->persist($entity);
            }
            $em->flush();

            $msgKey = ($newEntity ? FormMessages::CREATED : FormMessages::UPDATED);
            $this->flashHandler->add(
                'success',
                $msgKey,
                ['%name%' => $userEntityName]
            );

            return true;

        } else if ($form->isSubmitted()) {
            $this->flashHandler->add(
                'warning',
                FormMessages::VALIDATION_ERRORS
            );
        }

        return false;
    }

    /**
     * Creates a flat-ish array of the errors on the form,
     * keyed by their field name.
     * May contain nested arrays of errors if the form has child forms.
     * @todo
     *
     * @param FormInterface $form
     *
     * @return array
     */
    public function getFormErrors(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getFormErrors($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type    The fully qualified class name of the form type
     * @param mixed  $data    The initial data for the form
     * @param array  $options Options for the form
     *
     * @return Form|FormInterface
     */
    protected function createForm($type, $data, $options)
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route         The name of the route
     * @param mixed  $parameters    An array of parameters
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl($route, $parameters = [])
    {
        return $this->router->generate($route, $parameters);
    }
}