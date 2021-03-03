<?php

namespace Spartan\Template\Adapter;

use Spartan\Template\Definition\TemplateInterface;

/**
 * Phtml Adapter
 *
 * @package Spartan\Template
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Phtml implements TemplateInterface
{
    /**
     * @var mixed[]
     */
    protected static array $globalParams = [];

    /**
     * @var mixed[]
     */
    protected array $options = [
        'template' => null,
        'paths'    => [
            './resources/templates/actions',
            './resources/templates/layouts',
            './resources/templates/snippets',
        ],
    ];

    /**
     * @var mixed[]
     */
    protected array $filters = [];

    /**
     * @var mixed[]
     */
    protected array $helpers = [];

    /**
     * @var mixed[]
     */
    protected array $params = [];

    /**
     * @var mixed[]
     */
    protected array $inherited = [];

    /**
     * @var mixed[]
     */
    protected array $blocks = [];

    /**
     * @var string
     */
    protected string $content = '';

    /**
     * Phtml constructor.
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + $this->options;
    }

    /**
     * @param mixed[] $params
     *
     * @return $this
     */
    public function withGlobalParams(array $params): self
    {
        self::$globalParams = $params + self::$globalParams;

        return $this;
    }

    /**
     * @param mixed[] $params
     *
     * @return $this
     */
    public function withParams(array $params): self
    {
        $this->params = $params + $this->params;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function withTemplate(string $name): self
    {
        $this->options['template'] = $name;

        return $this;
    }

    /**
     * @param string|string[] $paths
     *
     * @return $this
     */
    public function withPaths($paths): self
    {
        $this->options['paths'] = (array)$paths;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function templateFile(string $name): string
    {
        $name = trim($name, '/');

        foreach ((array)$this->options['paths'] as $path) {
            if (file_exists("{$path}/{$name}.phtml")) {
                return "{$path}/{$name}.phtml";
            }
        }

        throw new \InvalidArgumentException("Template name not found: `{$name}`");
    }

    /**
     * @param string|string[] $inherit
     *
     * @return $this
     */
    public function inherit($inherit): self
    {
        foreach ((array)$inherit as $file) {
            $this->inherited[] = $file;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function inherited(): array
    {
        return $this->inherited;
    }

    /**
     * @return $this
     */
    public function resetInherited(): self
    {
        $this->inherited = [];

        return $this;
    }

    /**
     * @param mixed $value
     * @param mixed $filters
     *
     * @return mixed
     */
    public function filter($value, $filters)
    {
        $filters = $filters ? explode('|', $filters) : [];

        foreach ($filters as $filter) {
            if (isset($this->filters[$filter])) {
                $value = $this->filters[$filter]($value);
            } elseif (is_callable($filter)) {
                $value = $filter($value);
            } elseif (function_exists("str_{$filter}")) {
                $value = call_user_func("str_{$filter}", $value);
            } elseif (function_exists("str{$filter}")) {
                $value = call_user_func("str{$filter}", $value);
            }
        }

        return $value;
    }

    /**
     * @param mixed[] $filters
     *
     * @return $this
     */
    public function withFilters(array $filters): self
    {
        $this->filters = $filters + $this->filters;

        return $this;
    }

    /**
     * @param mixed[] $helpers
     *
     * @return self
     */
    public function withHelpers(array $helpers): self
    {
        $this->helpers = $helpers + $this->helpers;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param string|null $tplName
     * @param mixed[]     $params
     * @param mixed       $options
     *
     * @return string
     */
    public function render(string $tplName = null, array $params = [], $options = null): string
    {
        $this->options = (array)$options + $this->options;

        $tplName = $tplName ?: $this->options['template'];

        $this->inherit($tplName);

        extract($this->params);
        extract($params);
        extract(self::$globalParams);
        $t = $this; // shortcut inside template

        ob_start();

        while (count($this->inherited)) {
            include $this->templateFile(array_pop($this->inherited));
            $this->content = (string)ob_get_contents();
            ob_clean();
        }

        ob_end_clean();

        return (string)$this->content;
    }

    /**
     * @param mixed $name
     */
    public function block($name = null): void
    {
        if ($name === null) {
            if (count($this->blocks) == 0) {
                throw new \LogicException('No blocks started!');
            }

            $block = array_pop($this->blocks);

            // close block
            $this->params[$block] = ob_get_clean();
        } else {
            // open block
            $this->blocks[] = $name;

            ob_start();
        }
    }

    /**
     * @param string  $name
     * @param mixed[] $params
     *
     * @return string
     */
    public function insert(string $name, array $params = []): string
    {
        return (new self())->render($name, $params, $this->options);
    }

    /**
     * @param string  $helper Helper name
     * @param mixed[] $params Params
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __call($helper, array $params = [])
    {
        if (!isset($this->helpers[$helper])) {
            throw new \InvalidArgumentException("Helper is not defined: `{$helper}`");
        }

        return call_user_func_array($this->helpers[$helper], $params);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Get params within template via $t->name
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if (strpos($name, '|')) {
            [$name, $filters] = explode('|', $name, 2) + [null, ''];

            return $this->filter($this->params[$name] ?? $name, $filters);
        }

        return $this->params[$name] ?? null;
    }
}
