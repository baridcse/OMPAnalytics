<?php

namespace App\Integrations;

use App\Enums\Capability;
use App\Models\Integration;
use InvalidArgumentException;

class ProviderFactory
{
    /**
     * Resolve the concrete provider driver for an integration + capability.
     */
    public function forCapability(Integration $integration, Capability $capability): object
    {
        $class = config("integrations.providers.{$integration->provider}.capabilities.{$capability->value}");

        if ($class === null) {
            throw new InvalidArgumentException(
                "Provider [{$integration->provider}] does not support capability [{$capability->value}]."
            );
        }

        return app($class);
    }

    public function supports(Integration $integration, Capability $capability): bool
    {
        return config("integrations.providers.{$integration->provider}.capabilities.{$capability->value}") !== null;
    }

    /**
     * @return list<Capability>
     */
    public function capabilitiesFor(Integration $integration): array
    {
        $map = config("integrations.providers.{$integration->provider}.capabilities", []);

        return array_map(Capability::from(...), array_keys($map));
    }
}
