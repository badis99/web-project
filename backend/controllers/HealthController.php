<?php

declare(strict_types=1);

final class HealthController
{
    public function check(): array
    {
        return ['status' => 'ok'];
    }
}
