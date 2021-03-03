<?php

namespace Spartan\Template\Test;

use PHPUnit\Framework\TestCase;
use Spartan\Template\Adapter\Phtml;

/**
 * PhtmlTest Test
 *
 * @package Spartan\Template
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class PhtmlTest extends TestCase
{
    public function testInherited()
    {
        $tpl = new Phtml(
            [
                'paths' => [__DIR__ . '/templates'],
            ]
        );

        $this->assertSame(
            file_get_contents(__DIR__ . '/renders/phtml_inherited.phtml'),
            $tpl->inherit('layout')->render('template', ['name' => 'World'])
        );
    }
}
