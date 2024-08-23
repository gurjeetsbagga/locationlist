<?php

namespace Drupal\Tests\location_finder\Unit\Service;

use Drupal\location_finder\Service\LocationService;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Http\Discovery\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Unit tests for LocationService.
 */
class LocationServiceTest extends TestCase
{
    /**
     * @var ClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $httpClientMock;

    /**
     * @var ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configFactoryMock;

    /**
     * @var LocationService
     */
    protected $locationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientMock = $this->createMock(ClientInterface::class);
        $this->configFactoryMock = $this->createMock(ConfigFactoryInterface::class);
        $this->locationService = new LocationService($this->httpClientMock, $this->configFactoryMock);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetApiLocationSuccess(): void
    {
        $response_body = '{"locations": [{"name": "Location 1"}]}';

        $this->httpClientMock->method('request')
            ->willReturn($this->getResponseMock($response_body));

        $result = $this->locationService->getApiLocation('US', 'New York', '10001');

        $this->assertIsArray($result);
        $this->assertEquals('Location 1', $result['locations'][0]['name']);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetApiLocationThrowsNotFoundException(): void
    {
        $this->httpClientMock->method('request')
            ->willThrowException(new \Exception('API error'));

        $this->expectException(NotFoundException::class);
        $this->locationService->getApiLocation('US', 'New York', '10001');
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function testGetLocationsReturnsResultGetFilteredLocationsFunction(): void
    {
        $response_body = '{
            "locations": [
                {
                    "name": "Location 1",
                    "place": {
                        "address": {
                            "streetAddress": "Main St 1234"
                        }
                    },
                    "openingHours": []
                }
            ]
        }';

        $this->httpClientMock->method('request')
            ->willReturn($this->getResponseMock($response_body));

        $location_mock = $this->getMockBuilder(LocationService::class)
            ->setConstructorArgs([$this->httpClientMock, $this->configFactoryMock])
            ->onlyMethods(['getFilteredLocations'])
            ->getMock();

        $location_mock->method('getFilteredLocations')
            ->willReturn(['Filtered Location Data']);

        $result = $location_mock->getLocations('US', 'New York', '10001');

        $this->assertEquals(['Filtered Location Data'], $result);
    }

    /**
     * @dataProvider missingDataProvider
     * @throws       GuzzleException
     */
    public function testGetLocationsThrowsExceptionForEmptyParams(
        string $countryCode,
        string $city,
        string $postalCode
    ): void {
        $this->expectException(\Exception::class);
        $this->locationService->getLocations($countryCode, $city, $postalCode);
    }

    public function missingDataProvider(): array
    {
        return [
            ['', 'New York', '10001'],
            ['US', '', '10001'],
            ['US', 'New York', ''],
            ['', '', ''],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function testGetLocationsReturnsEmptyArrayWithNotExistInputData(): void
    {
        $this->httpClientMock->method('request')
            ->willReturn($this->getResponseMock('{}'));

        $result = $this->locationService->getLocations('US', 'New York', '10001');
        $this->assertEmpty($result);
    }

    /**
     * @dataProvider filteredLocationsProvider
     */
    public function testGetFilteredLocationsSuccesful(array $locations, array $expectedOutput): void
    {
        $location_mock = $this->getMockBuilder(LocationService::class)
            ->setConstructorArgs([$this->httpClientMock, $this->configFactoryMock])
            ->onlyMethods(['getOpeningHours', 'isOddNumberAddress'])
            ->getMock();

        $location_mock->method('getOpeningHours')
            ->willReturnOnConsecutiveCalls(['Saturday' => '08:00-18:00'], []);

        $location_mock->method('isOddNumberAddress')
            ->willReturnOnConsecutiveCalls(false, true);

        $result = $location_mock->getFilteredLocations($locations);

        $this->assertEquals($expectedOutput, $result);
    }

    public function filteredLocationsProvider(): array
    {
        return [
            [
                [
                    'locations' => [
                        [
                          'name' => 'Location 1',
                          'place' => ['address' => ['streetAddress' => 'Main St 1234']],
                          'openingHours' => [],
                        ],
                        [
                          'name' => 'Location 2',
                          'place' => ['address' => ['streetAddress' => 'Elm St 1235']],
                          'openingHours' => [],
                        ],
                    ],
                ],
                [
                    Yaml::dump(
                        [
                          'locationName' => 'Location 1',
                          'address' => ['streetAddress' => 'Main St 1234'],
                          'openingHours' => ['Saturday' => '08:00-18:00'],
                        ],
                        2,
                        4,
                        Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
                    ),
                ],
            ]
        ];
    }

    public function testGetOpeningHoursReturnsForWeekEndDaysOnly(): void
    {
        $location = [
            'openingHours' => [
                [
                  'dayOfWeek' => 'https://schema.org/Saturday',
                  'opens' => '08:00',
                  'closes' => '18:00',
                ],
                [
                  'dayOfWeek' => 'https://schema.org/Sunday',
                  'opens' => '10:00',
                  'closes' => '16:00',
                ],
            ],
        ];

        $result = $this->locationService->getOpeningHours($location);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Saturday', $result);
        $this->assertArrayHasKey('Sunday', $result);
    }

    public function testGetOpeningHoursReturnsEmptyForNoOpeningHours(): void
    {
        $location = [
            'openingHours' => []
        ];

        $result = $this->locationService->getOpeningHours($location);

        $this->assertEmpty($result);
    }

    public function testGetOpeningHoursReturnsEmptyForEmptyDays(): void
    {
        $location = [
            'openingHours' => [
                [
                  'dayOfWeek' => '',
                  'opens' => '09:00',
                  'closes' => '17:00',
                ],
                [
                  'dayOfWeek' => '',
                  'opens' => '09:00',
                  'closes' => '17:00',
                ],
            ],
        ];

        $result = $this->locationService->getOpeningHours($location);

        $this->assertEmpty($result);
    }


    /**
     * @dataProvider isOddNumberAddressProvider
     */
    public function testIsOddNumberAddress(string $streetAddress, bool $expectedResult): void
    {
        $result = $this->locationService->isOddNumberAddress($streetAddress);
        $this->assertEquals($expectedResult, $result);
    }

    public function isOddNumberAddressProvider(): array
    {
        return [
            ['Main St 123', true],
            ['Main St 124', false],
            ['Broadway 125', true],
            ['Broadway 200', false],
            ['', false],
        ];
    }

    private function getResponseMock(string $responseBody): ResponseInterface
    {
        $response_mock = $this->createMock(ResponseInterface::class);
        $stream_mock = $this->createMock(StreamInterface::class);

        $stream_mock->method('getContents')
            ->willReturn($responseBody);

        $response_mock->method('getBody')
            ->willReturn($stream_mock);

        return $response_mock;
    }
}
