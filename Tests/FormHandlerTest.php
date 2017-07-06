<?php

namespace XM\FilterBundle\Tests;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use XM\FlashBundle\FlashHandlerInterface;
use XM\FormBundle\FormHandler;
use XM\FormBundle\FormMessages;

class FormHandlerTest extends \PHPUnit_Framework_TestCase
{

    public function testGetForm()
    {
        $formClass = '\AppBundle\Form\ClassType';
        $route = '/form/submit';

        $entity = \Mockery::mock(\StdClass::class, [
            'getId' => null,
        ]);

        $request = \Mockery::mock(Request::class);
        $attributes = \Mockery::mock(ParameterBag::class, [
            'get' => 'new_route'
        ]);
        $request->attributes = $attributes;

        $formOptions = [
            'method' => 'POST',
            'action' => $route,
        ];
        $form = \Mockery::mock(Form::class, function ($mock) use ($request) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('handleRequest', 1)
                ->withArgs([$request]);
        });

        $formFactory = \Mockery::mock(
            FormFactory::class,
            function ($mock) use ($entity, $formOptions, $form, $formClass) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldReceive('create', 1)
                    ->withArgs(
                        [$formClass, $entity, $formOptions]
                    )
                    ->andReturn($form);
            }
        );
        $router = $this->getRouterMock('new_route', [], $route);

        $formHandler = $this->getFormHandler($formFactory, null, $router, null);
        $result = $formHandler->getForm($formClass, $entity, $request);

        $this->assertEquals($form, $result);
    }

    /**
     * Build form options as new entity.
     */
    public function testBuildFormOptionsNew()
    {
        $router = $this->getRouterMock('new_route', [], '/form/submit');

        $entity = \Mockery::mock(\StdClass::class, [
            'getId' => null,
        ]);
        $request = \Mockery::mock(Request::class);
        $attributes = \Mockery::mock(ParameterBag::class, [
            'get' => 'new_route'
        ]);
        $request->attributes = $attributes;

        $formHandler = $this->getFormHandler(null, null, $router, null);
        $result = $formHandler->buildFormOptions($entity, $request);

        $expected = [
            'method' => 'POST',
            'action' => '/form/submit',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Build form options as update.
     */
    public function testBuildFormOptionsUpdate()
    {
        $router = $this->getRouterMock('update_route', ['id' => 123], '/form/submit/123');

        $entity = \Mockery::mock(\StdClass::class, [
            'getId' => 123,
        ]);
        $request = \Mockery::mock(Request::class);
        $attributes = \Mockery::mock(ParameterBag::class, [
            'get' => 'update_route'
        ]);
        $request->attributes = $attributes;

        $formHandler = $this->getFormHandler(null, null, $router, null);
        $result = $formHandler->buildFormOptions($entity, $request);

        $expected = [
            'method' => 'PUT',
            'action' => '/form/submit/123',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Build form with additional "custom" options.
     */
    public function testBuildFormOptionsCustom()
    {
        $router = $this->getRouterMock('new_route', [], '/form/submit');

        $entity = \Mockery::mock(\StdClass::class, [
            'getId' => null,
        ]);
        $request = \Mockery::mock(Request::class);
        $attributes = \Mockery::mock(ParameterBag::class, [
            'get' => 'new_route'
        ]);
        $request->attributes = $attributes;

        $formHandler = $this->getFormHandler(null, null, $router, null);
        $result = $formHandler->buildFormOptions($entity, $request, [
            'other_option' => 'value',
        ]);

        $expected = [
            'method' => 'POST',
            'action' => '/form/submit',
            'other_option' => 'value',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Build form with additional "custom" options.
     */
    public function testBuildFormOptionsOverrideOptions()
    {
        $router = $this->getRouterMock('new_route', [], '/form/submit');

        $entity = \Mockery::mock(\StdClass::class, [
            'getId' => null,
        ]);
        $request = \Mockery::mock(Request::class);
        $attributes = \Mockery::mock(ParameterBag::class, [
            'get' => 'new_route'
        ]);
        $request->attributes = $attributes;

        $formHandler = $this->getFormHandler(null, null, $router, null);
        $result = $formHandler->buildFormOptions($entity, $request, [
            'action' => '/form2/submit/1234',
        ]);

        $expected = [
            'method' => 'POST',
            'action' => '/form2/submit/1234',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Process form with new entity.
     */
    public function testProcessFormNew()
    {
        $form = \Mockery::mock(Form::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('isSubmitted', 1)
                ->andReturn(true);
            $mock->shouldReceive('isValid')
                ->andReturn(true);
        });

        $entity = \Mockery::mock(\StdClass::class);

        $em = \Mockery::mock(ObjectManager::class, function ($mock) use ($entity) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('contains', 1)
                ->withArgs([$entity])
                ->andReturn(false);
            $mock->shouldReceive('persist', 1);
            $mock->shouldReceive('flush', 1);
        });

        $flashHandler = \Mockery::mock(FlashHandlerInterface::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('add', 1)
                ->withArgs(['success', FormMessages::CREATED, ['%name%' => 'Record']]);
        });

        $formHandler = $this->getFormHandler(null, $em, null, $flashHandler);
        $result = $formHandler->processForm($form, $entity, 'Record');

        $this->assertTrue($result);
    }

    /**
     * Process form with existing entity.
     */
    public function testProcessFormUpdate()
    {
        $form = \Mockery::mock(Form::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('isSubmitted', 1)
                ->andReturn(true);
            $mock->shouldReceive('isValid')
                ->andReturn(true);
        });

        $entity = \Mockery::mock(\StdClass::class);

        $em = \Mockery::mock(ObjectManager::class, function ($mock) use ($entity) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('contains', 1)
                ->withArgs([$entity])
                ->andReturn(true);
            $mock->shouldNotReceive('persist');
            $mock->shouldReceive('flush', 1);
        });

        $flashHandler = \Mockery::mock(FlashHandlerInterface::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('add', 1)
                ->withArgs(['success', FormMessages::UPDATED, ['%name%' => 'Record']]);
        });

        $formHandler = $this->getFormHandler(null, $em, null, $flashHandler);
        $result = $formHandler->processForm($form, $entity, 'Record');

        $this->assertTrue($result);
    }

    /**
     * Process form with invalid entity.
     */
    public function testProcessFormInvalid()
    {
        $form = \Mockery::mock(Form::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('isSubmitted', 1)
                ->andReturn(true);
            $mock->shouldReceive('isValid')
                ->andReturn(false);
        });

        $entity = \Mockery::mock(\StdClass::class);

        $flashHandler = \Mockery::mock(FlashHandlerInterface::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('add', 1)
                ->withArgs(['warning', FormMessages::VALIDATION_ERRORS]);
        });

        $formHandler = $this->getFormHandler(null, null, null, $flashHandler);
        $result = $formHandler->processForm($form, $entity, 'Record');

        $this->assertFalse($result);
    }

    /**
     * Process form when the form has not been submitted.
     */
    public function testProcessFormNotSubmitted()
    {
        $form = \Mockery::mock(Form::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('isSubmitted', 1)
                ->andReturn(false);
            $mock->shouldNotReceive('isValid');
        });

        $entity = \Mockery::mock(\StdClass::class);

        $formHandler = $this->getFormHandler(null, null, null, null);
        $result = $formHandler->processForm($form, $entity, 'Record');

        $this->assertFalse($result);
    }

    /**
     * @param FormFactoryInterface $formFactory
     * @param ObjectManager $em
     * @param Router $router
     * @param FlashHandlerInterface $flashHandler
     * @return FormHandler
     */
    protected function getFormHandler($formFactory = null, $em = null, $router = null, $flashHandler = null)
    {
        if ($formFactory === null) {
            $formFactory = \Mockery::mock(FormFactory::class);
        }
        if ($em === null) {
            $em = \Mockery::mock(ObjectManager::class);
        }
        if ($router === null) {
            $router = \Mockery::mock(Router::class);
        }
        if ($flashHandler === null) {
            $flashHandler = \Mockery::mock(FlashHandlerInterface::class);
        }

        return new FormHandler($formFactory, $em, $router, $flashHandler);
    }

    protected function getRouterMock($route, $params, $return)
    {
        return \Mockery::mock(
            Router::class,
            function ($mock) use ($route, $params, $return) {
                /** @var $mock \Mockery\MockInterface */
                $mock->shouldReceive('generate', 1)
                    ->withArgs([$route, $params])
                    ->andReturn($return);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}