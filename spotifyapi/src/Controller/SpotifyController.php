<?php

namespace Drupal\spotifyapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\spotifyapi\SpotifyApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SpotifyController extends ControllerBase implements ContainerInjectionInterface {

  /** @var \Drupal\spotifyapi\SpotifyApi */
  protected $spotifyApi;

  public function __construct(SpotifyApi $spotifyApi) {
    $this->spotifyApi = $spotifyApi;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spotifyapi.spotifyapi')
    );
  }

  public function viewArtist(Request $request, $artist) {
    $artistData = $this->spotifyApi->getArtistDetails($artist);
  }

}