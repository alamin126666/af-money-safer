<?php
declare(strict_types=1);

namespace App\Core;

/** Expected, user-facing failure. $errorCode maps to a message in lang/*.php. */
final class ApiException extends \Exception
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status = 400,
        public readonly array $data = []
    ) {
        parent::__construct($errorCode);
    }
}
