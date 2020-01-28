<?php
declare(strict_types=1);

namespace PhpSrcErrorParser;

class Pattern
{
    public string $pattern;
    public string $zendFunction;
    public ?string $zendException;

    public function __construct(
        string $pattern,
        string $zendFunction,
        ?string $zendException = null
    ) {
        $this->pattern = $pattern;
        $this->zendFunction = $zendFunction;
        $this->zendException = $zendException;
    }
}
