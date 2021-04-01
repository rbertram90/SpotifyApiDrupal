<?php

namespace Drupal\spotifyapi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotifyapi\SpotifyApi;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Spotify' Block.
 *
 * @Block(
 *   id = "spotify_artist_list",
 *   admin_label = @Translation("Spotify Artist List"),
 * )
 */
class SpotifyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\spotifyapi\SpotifyApi */
  protected $spotifyApi;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SpotifyApi $spotifyApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->spotifyApi = $spotifyApi;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('spotifyapi.spotifyapi')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['artist_count'] = [
      '#type' => 'select',
      '#options' => range(1, 20),
      '#title' => $this->t('How many artists to show'),
      '#required' => TRUE,
      '#default_value' => isset($config['artist_count']) ? $config['artist_count'] : 9, // zero indexed default = 10 artists
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['artist_count'] = $values['artist_count'];
  }

  public function build() {
    // Starting point to get further artists
    $my_favourite_artist = '3rIZMv9rysU7JkLzEaC5Jp';
    $config = $this->getConfiguration();
    $related_artists = $this->spotifyApi->getArtistsData($my_favourite_artist, $config['artist_count']);

    return [
      '#markup' => json_encode($related_artists)
    ];
  }

  public function getCacheMaxAge()
  {
    return 0;
  }

}