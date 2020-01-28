<?php
declare(strict_types=1);

namespace PhpSrcErrorParser;


class ErrorCall implements \JsonSerializable
{
    public string $errorMessage;
    public array $occurences;
    public ?string $hugger = null;

    public function __construct(
        string $errorMessage,
        string $file,
        int $line,
        string $zendFunction,
        array $args,
        string $zendException
    ) {
        $this->errorMessage = $errorMessage;

        $this->addOccurence(
            $file,
            $line,
            $zendFunction,
            $args,
            $zendException
        );
    }

    public function addOccurence(
        string $file,
        int $line,
        string $function,
        array $args,
        string $exception,
        ?string $level = null
    ): void {
        $this->occurences[] = [
            'file' => $file,
            'line' => $line,
            'function' => $function,
            'args' => $args,
            'exception' => $exception,
            'level' => $level ?? $this->extractErrorLevel($args),
        ];
    }

    public function addHug(string $hug): void
    {
        $this->hugger = $hug;
    }

    private function extractErrorLevel(array $args): ?string
    {
        foreach ($args as $arg) {
            if (strpos($arg, 'E_') === 0) {
                return $arg;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'message' => $this->errorMessage,
            'occ' => $this->occurences,
            'hugger' => $this->hugger,
        ];
    }
}
