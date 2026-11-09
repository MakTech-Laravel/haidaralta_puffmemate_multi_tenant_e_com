<?php

namespace App\Services\Maps\Data;

final readonly class GeocodeResult
{
    /**
     * @param  array<string, string|null>  $address
     */
    public function __construct(
        public string $label,
        public float $latitude,
        public float $longitude,
        public string $provider,
        public array $address = [],
    ) {}

    /**
     * @return array{label: string, latitude: float, longitude: float, provider: string, address: array<string, string|null>}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'provider' => $this->provider,
            'address' => $this->address,
        ];
    }

    /**
     * @param  array{label: string, latitude: float|int|string, longitude: float|int|string, provider: string, address?: array<string, string|null>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            provider: $data['provider'],
            address: $data['address'] ?? [],
        );
    }
}
