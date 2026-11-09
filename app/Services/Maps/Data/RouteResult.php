<?php

namespace App\Services\Maps\Data;

final readonly class RouteResult
{
    /**
     * @param  list<array{0: float, 1: float}>  $points  [latitude, longitude] pairs along the route
     */
    public function __construct(
        public int $distanceMeters,
        public int $durationSeconds,
        public array $points,
        public string $provider,
    ) {}

    /**
     * @return array{distance_meters: int, duration_seconds: int, points: list<array{0: float, 1: float}>, provider: string}
     */
    public function toArray(): array
    {
        return [
            'distance_meters' => $this->distanceMeters,
            'duration_seconds' => $this->durationSeconds,
            'points' => $this->points,
            'provider' => $this->provider,
        ];
    }

    /**
     * @param  array{distance_meters: int, duration_seconds: int, points: list<array{0: float, 1: float}>, provider: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            distanceMeters: (int) $data['distance_meters'],
            durationSeconds: (int) $data['duration_seconds'],
            points: $data['points'],
            provider: $data['provider'],
        );
    }
}
