<?php

namespace XM\FilterBundle\Tests;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Translation\TranslatorInterface;

class FormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}