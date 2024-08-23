<?php

declare(strict_types=1);

namespace Drupal\location_finder\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Http\Discovery\Exception\NotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * For Fetching data from api
 */
class LocationService
{
    /**
     * @var ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * @var ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * @var string[]
     */
    protected $filteredDays = [
      'Saturday',
      'Sunday'
    ];

    /**
     * @param ClientInterface        $httpClient
     * @param ConfigFactoryInterface $configFactory
     */
    public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory)
    {
        $this->httpClient = $httpClient;
        $this->configFactory = $configFactory;
    }

    /**
     *  Get Location from api.
     *  Then, filtered the location, which should be open on weekends
     *  and having even number in their street address
     *
     * @param  string $countryCode
     * @param  string $city
     * @param  string $postalCode
     * @return array
     * @throws GuzzleException
     * @throws \Exception
     */
    public function getLocations(string $countryCode, string $city, string $postalCode): array
    {
        if (empty($countryCode) || empty($city) || empty($postalCode)) {
            throw new \Exception('Country, city or postal code should be provided');
        }

        $locations = $this->getApiLocation($countryCode, $city, $postalCode);

        if (empty($locations)) {
            return [];
        }

        return  $this->getFilteredLocations($locations);
    }

    /**
     * @param  $countryCode
     * @param  $city
     * @param  $postalCode
     * @return mixed
     * @throws GuzzleException
     */
    public function getApiLocation($countryCode, $city, $postalCode): mixed
    {
        $query = http_build_query(
            [
              'countryCode' => $countryCode,
              'addressLocality' => $city,
              'postalCode' => $postalCode,
            ]
        );

        $endpoint = 'https://api.dhl.com/location-finder/v1/find-by-address?' . $query;

        $parameters = [
            'headers' => [
                'Accept' => 'application/json',
                'DHL-API-Key' => 'demo-key'
            ],
        ];

        try {
            $response = $this->httpClient->request('GET', $endpoint, $parameters);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $exception) {
            throw new NotFoundException($exception->getMessage());
        }
    }

    /**
     * @param  $locations
     * @return array
     */
    public function getFilteredLocations($locations): array
    {
        $yaml_output = [];

        foreach ($locations['locations'] as $location) {
            $opening_hours = $this->getOpeningHours($location);

            if (empty($opening_hours) || $this->isOddNumberAddress($location['place']['address']['streetAddress'])) {
                continue;
            }

            $result = [
                'locationName'  => $location['name'],
                'address'       => $location['place']['address'],
                'openingHours'  => $opening_hours,
            ];

            $yaml_output[] = Yaml::dump($result, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        }

        return $yaml_output;
    }

    /**
     * @param  $location
     * @return array
     */
    public function getOpeningHours($location): array
    {
        $opening_hours = [];
        $days_of_week = [];

        foreach ($location['openingHours'] as $key => $value) {
            if (empty($value['dayOfWeek'])) {
                continue;
            }

            $day_of_week = trim(last(explode('/', $value['dayOfWeek'])));
            $opening_hours[$day_of_week] = $value['opens'] . '-' . $value['closes'];
            $days_of_week[] = $day_of_week;
        }

        if (empty(array_intersect($days_of_week, $this->filteredDays))) {
            $opening_hours = [];
        }

        return $opening_hours;
    }

    /**
     * @param  $streetAddress
     * @return bool
     */
    public function isOddNumberAddress($streetAddress): bool
    {
        if (empty($streetAddress)) {
            return false;
        }

        $address = last(explode(' ', $streetAddress));

        if (is_numeric($address) && intval($address) % 2 !== 0) {
            return true;
        }

        return false;
    }
}
