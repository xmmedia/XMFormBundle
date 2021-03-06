<?php

namespace XM\FormBundle;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use XM\FlashBundle\FlashHandlerInterface;

class FormHandler
{
    protected $formFactory;
    protected $em;
    protected $router;
    protected $flashHandler;

    public function __construct(
        FormFactoryInterface $formFactory,
        ObjectManager $em,
        RouterInterface $router,
        FlashHandlerInterface $flashHandler
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
     * @return \Symfony\Component\Form\FormInterface
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

        return $options;
    }

    /**
     * Processes the form, including checking if the input is valid.
     * Also persists the entity if it's a new entity and flushes the em.
     * Adds the appropriate flash messages (created, updated or invalid).
     * Returns true if everything has been saved.
     * Returns false if there was an error and the form should be displayed again.
     *
     * @param FormInterface $form The form.
     * @param object $entity The entity
     * @param string $userEntityName The name of entity to use in flash messages
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     *
     * @return bool
     */
    public function processForm(
        FormInterface $form,
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
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type    The fully qualified class name of the form type
     * @param mixed  $data    The initial data for the form
     * @param array  $options Options for the form
     *
     * @return \Symfony\Component\Form\FormInterface
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
