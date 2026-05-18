<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Geo\Services\GeoJsonNormalizer;
use Tests\TestCase;

class GeoJsonNormalizerTest extends TestCase
{
    public function test_normalizes_null_or_empty_to_null(): void
    {
        $this->assertNull(GeoJsonNormalizer::toMultiPolygon(null));
        $this->assertNull(GeoJsonNormalizer::toMultiPolygon(''));
        $this->assertNull(GeoJsonNormalizer::toMultiPolygon([]));
    }

    public function test_normalizes_polygon_to_multipolygon(): void
    {
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [10.0, 10.0],
                    [20.0, 10.0],
                    [20.0, 20.0],
                    [10.0, 20.0],
                    [10.0, 10.0]
                ]
            ]
        ];

        $expected = [
            'type' => 'MultiPolygon',
            'coordinates' => [
                [
                    [
                        [10.0, 10.0],
                        [20.0, 10.0],
                        [20.0, 20.0],
                        [10.0, 20.0],
                        [10.0, 10.0]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, GeoJsonNormalizer::toMultiPolygon($polygon));
    }

    public function test_closes_unclosed_polygon_rings(): void
    {
        $unclosedPolygon = [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [10.0, 10.0],
                    [20.0, 10.0],
                    [20.0, 20.0],
                    [10.0, 20.0]
                ]
            ]
        ];

        $result = GeoJsonNormalizer::toMultiPolygon($unclosedPolygon);
        $this->assertNotNull($result);
        
        $coords = $result['coordinates'][0][0];
        $this->assertCount(5, $coords);
        $this->assertEquals([10.0, 10.0], $coords[0]);
        $this->assertEquals([10.0, 10.0], $coords[4]);
    }

    public function test_normalizes_feature_to_multipolygon(): void
    {
        $feature = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [1.0, 1.0], [2.0, 1.0], [2.0, 2.0], [1.0, 2.0], [1.0, 1.0]
                    ]
                ]
            ]
        ];

        $result = GeoJsonNormalizer::toMultiPolygon($feature);
        $this->assertEquals('MultiPolygon', $result['type']);
        $this->assertEquals([[[[1.0, 1.0], [2.0, 1.0], [2.0, 2.0], [1.0, 2.0], [1.0, 1.0]]]], $result['coordinates']);
    }

    public function test_normalizes_feature_collection_to_multipolygon(): void
    {
        $collection = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [
                            [
                                [1.0, 1.0], [2.0, 1.0], [2.0, 2.0], [1.0, 2.0], [1.0, 1.0]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'MultiPolygon',
                        'coordinates' => [
                            [
                                [
                                    [3.0, 3.0], [4.0, 3.0], [4.0, 4.0], [3.0, 4.0], [3.0, 3.0]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = GeoJsonNormalizer::toMultiPolygon($collection);
        $this->assertEquals('MultiPolygon', $result['type']);
        $this->assertCount(2, $result['coordinates']);
    }
}
