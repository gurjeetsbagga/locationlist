<?php

declare(strict_types=1);

namespace Drupal\location_finder\Controller;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\location_finder\Service\LocationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use to fetch Location List having:
 * 1. Even Number Street Address
 * 2. Locations, Working on weekends
 */
class LocationController extends ControllerBase
{
    /**
     * @var LocationService
     */
    private LocationService $locationService;

    /**
     * @param LocationService $locationService
     */
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * @param  ContainerInterface $container
     * @return static
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('location_finder.service')
        );
    }

    /**
     * @param  Request $request
     * @return Response
     * @throws GuzzleException
     */
    public function getLocationList(Request $request): Response
    {
        $country_code = $request->query->get('country_code');
        $city = $request->query->get('city');
        $postal_code = $request->query->get('postal_code');

        $yaml_output = $this->locationService->getLocations($country_code, $city, $postal_code);

        return new Response(implode("\n---\n", $yaml_output), 200, ['Content-Type' => 'text/plain']);
    }
}
