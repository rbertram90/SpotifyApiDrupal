<?php

namespace Drupal\spotifyapi;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class SpotifyApi {

    /** @var \GuzzleHttp\ClientInterface */
    protected $httpClient;

    /** @var \Drupal\Core\Config\ImmutableConfig */
    protected $config;

    /** @var \Drupal\Core\State\StateInterface */
    protected $state;

    protected const SPOTIFY_BASE_URL = 'https://accounts.spotify.com';
    protected const SPOTIFY_API_BASE_URL = 'https://api.spotify.com';

    public function __construct(ClientInterface $client, ConfigFactoryInterface $configFactory, StateInterface $state)
    {
        $this->httpClient = $client;
        $this->config = $configFactory->get('spotifyapi.settings');
        $this->state = $state;
    }

    public function getArtistDetails($artist) {
        $auth_token = $this->getAuthToken();

        // Hard coded starting artist for now
        try {
            $response = $this->httpClient->request('GET', self::SPOTIFY_API_BASE_URL . "/v1/artist/{$artist}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth_token
                ]
            ]);
        }
        catch (GuzzleException $e) {
            \Drupal::messenger()->addMessage('Unable to get artist data', MessengerInterface::TYPE_ERROR);
            return [];
        }

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody()->getContents(), TRUE);
        }

        return [];
    }

    public function getArtistsData($artist, $limit = 10) {

        $auth_token = $this->getAuthToken();

        // Hard coded starting artist for now
        try {
            $response = $this->httpClient->request('GET', self::SPOTIFY_API_BASE_URL . "/v1/artists/{$artist}/related-artists", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth_token
                ],
                'query' => [
                    'limit' => $limit
                ]
            ]);
        }
        catch (GuzzleException $e) {
            \Drupal::messenger()->addMessage('Unable to get related artists: ' . $e->getMessage(), MessengerInterface::TYPE_ERROR);
            return [];
        }

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody()->getContents(), TRUE);
        }

        return [];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     * 
     * @return string
     *   Auth token
     */
    public function getAuthToken() {
        $token = $this->state->get('spotifyapi.token');
        $expiry = $this->state->get('spotifyapi.expires');

        // Check if we've already got a valid token
        if ($expiry && $token && $expiry > time()) {
            return $token;
        }

        // set using $config['spotifyapi.settings']['api_client_id'] in settings.php or through config form
        $client_id = $this->config->get('api_client_id');

        // set using $config['spotifyapi.settings']['api_client_secret'] in settings.php or through config form
        $client_secret = $this->config->get('api_client_secret');
        
        $basic_auth = base64_encode($client_id . ':' . $client_secret);

        $response = $this->httpClient->request('POST', self::SPOTIFY_BASE_URL . '/api/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
            'headers' => [
                'Authorization' => 'Basic ' . $basic_auth
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $response_body = $response->getBody()->getContents();
            $response_data = json_decode($response_body);

            $this->state->set('spotifyapi.token', $response_data->access_token);
            $this->state->set('spotifyapi.expires', time() + $response_data->expires_in);

            return $response_data->access_token;
        }

        throw new \Exception('Did not get valid response');
    }

}