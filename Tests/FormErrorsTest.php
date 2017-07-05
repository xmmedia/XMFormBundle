<?php

namespace XM\FormBundle\Tests;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use XM\FormBundle\FormErrors;

class FormErrorsTest extends \PHPUnit_Framework_TestCase
{
    public function testFlatten()
    {
        $globalError = \Mockery::mock(\StdClass::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('getMessage', 1)
                ->andReturn('Global Error');
        });

        $childChildError = \Mockery::mock(\StdClass::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('getMessage', 1)
                ->andReturn('Child Child Error');
        });
        $childChild = \Mockery::mock(FormInterface::class, function ($mock) use ($childChildError) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('getErrors', 1)
                ->andReturn([$childChildError]);
            $mock->shouldReceive('getName', 1)
                ->andReturn('child_child');
            $mock->shouldReceive('all', 1)
                ->andReturn([]);
        });

        $childError = \Mockery::mock(\StdClass::class, function ($mock) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('getMessage', 1)
                ->andReturn('Child Error');
        });
        $child = \Mockery::mock(FormInterface::class, function ($mock) use ($childError, $childChild) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('getErrors', 1)
                ->andReturn([$childError]);
            $mock->shouldReceive('getName', 1)
                ->andReturn('child');
            $mock->shouldReceive('all', 1)
                ->andReturn([$childChild]);
        });

        $form = \Mockery::mock(Form::class, function ($mock) use ($globalError, $child) {
            /** @var $mock \Mockery\MockInterface */
            $mock->shouldReceive('getErrors', 1)
                ->andReturn([$globalError]);
            $mock->shouldReceive('all', 1)
                ->andReturn([$child]);
        });

        $result = FormErrors::flatten($form);

        $expected = [
            'Global Error',
            'child' => [
                'Child Error',
                'child_child' => ['Child Child Error']
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}