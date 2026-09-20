<?php

use App\Support\BranchMap;

test('branch map links point at the pin on google and openstreetmap', function () {
    $links = BranchMap::links(-33.8708, 151.2073);

    expect($links)->toMatchArray([
        'google' => 'https://www.google.com/maps?q=-33.8708000,151.2073000',
        'open_street_map' => 'https://www.openstreetmap.org/?mlat=-33.8708000&mlon=151.2073000#map=16/-33.8708000/151.2073000',
        'embed' => 'https://www.openstreetmap.org/export/embed.html?bbox=151.1953000,-33.8828000,151.2193000,-33.8588000&layer=mapnik&marker=-33.8708000,151.2073000',
    ]);
});

test('branch map links are omitted without coordinates', function () {
    expect(BranchMap::links(null, 151.2073))->toBeNull()
        ->and(BranchMap::links(-33.8708, null))->toBeNull()
        ->and(BranchMap::links('', ''))->toBeNull();
});
