<?php

declare(strict_types=1);

namespace App\Contracts;

interface JsonHttpClientInterface
{
    public function get(string $url): array;
}
