<?php

namespace Spartan\Template\Definition;

/**
 * TemplateInterface
 *
 * @package Spartan\Template
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
interface TemplateInterface
{
    /**
     * @param string|null $name
     * @param mixed[]     $params
     * @param mixed       $options
     *
     * @return string
     */
    public function render(string $name = null, array $params = [], $options = null): string;
}
