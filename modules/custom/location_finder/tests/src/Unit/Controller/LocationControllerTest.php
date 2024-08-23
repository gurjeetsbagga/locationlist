<?php

declare(strict_types=1);

namespace Drupal\Tests\location_finder\Unit\Controller;

use Drupal\location_finder\Controller\LocationController;
use Drupal\location_finder\Service\LocationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\location_finder\Controller\LocationController
 */
class LocationControllerTest extends TestCase
{
    /**
     * @var LocationService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $locationServiceMock;

    /**
     * @var LocationController
     */
    private $locationController;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->locationServiceMock = $this->createMock(LocationService::class);
        $this->locationController = new LocationController($this->locationServiceMock);
    }

    /**
     * Tests the getLocationList method.
     *
     * @covers ::getLocationList
     */
    public function testGetLocationListSuccess(): void
    {
        $yaml_actual_output = ['locationName: Postfiliale 502', 'locationName: Packstation 145'];
        $yaml_expected_output = "locationName: Postfiliale 502\n---\nlocationName: Packstation 145";

        $request = new Request(
            [
            'country_code' => 'DE',
            'city' => 'BONN',
            'postal_code' => '53113'
            ]
        );

        $this->locationServiceMock->expects($this->once())
            ->method('getLocations')
            ->with('DE', 'BONN', '53113')
            ->willReturn($yaml_actual_output);

        $response = $this->locationController->getLocationList($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
        $this->assertEquals($yaml_expected_output, $response->getContent());
    }
}
