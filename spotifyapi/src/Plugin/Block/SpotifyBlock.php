<?php

namespace Drupal\spotifyapi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotifyapi\SpotifyApi;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Related artists list' Block with data sourced from Spotify.
 *
 * @Block(
 *   id = "spotify_artist_list",
 *   admin_label = @Translation("Related Artist List"),
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
      '#default_value' => isset($config['artist_count']) ? $config['artist_count'] : 9, // zero indexed, default = 10 artists
    ];

    $form['artist_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist ID to get related artists for'),
      '#required' => TRUE,
      '#default_value' => isset($config['artist_id']) ? $config['artist_id'] : '',
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
    $this->configuration['artist_id'] = $values['artist_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Starting point to get further artists
    $config = $this->getConfiguration();
    $related_artists = $this->spotifyApi->getRelatedArtists($config['artist_id'], $config['artist_count'] + 1);

    return [
      '#theme' => 'artist_list',
      '#artists' => $related_artists['artists']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 60 * 60 * 24; // 1 day
  }

}
