<?php


namespace Edrep\Google;


class ClientHandler
{
    private $appName;
    private $authConfigPath;
    private $accessTokenPath;
    private $scopes;

    private $googleClient;

    /**
     * ClientHandler constructor.
     * @param string $appName Application Name
     * @param string $authConfigPath credentials.json path
     * @param string $accessTokenPath token.json path
     * @param string|array $scopes Scopes Default: GMAIL_READONLY
     */
    public function __construct($appName, $authConfigPath, $accessTokenPath, $scopes = \Google_Service_Gmail::GMAIL_READONLY)
    {
        $this->appName = $appName;
        $this->authConfigPath = $authConfigPath;
        $this->accessTokenPath = $accessTokenPath;
        $this->scopes = $scopes;
    }

    public function getGoogleClient()
    {
        if (empty($this->googleClient)) {
            $this->buildGoogleClient();
        }

        return $this->googleClient;
    }

    /**
     * Builds the Google_Client and stores it in $this->googleClient
     *
     * @throws \Google_Exception
     * @throws \ErrorException
     */
    private function buildGoogleClient(): void
    {
        $this->googleClient = $this->initGoogleClient();

        // If there is no previous token or it's expired.
        if ($this->googleClient->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($this->googleClient->getRefreshToken()) {
                $this->googleClient->fetchAccessTokenWithRefreshToken($this->googleClient->getRefreshToken());
            } else {
                $this->requestAuthorization();
            }

            // Make sure the token destination directory exists
            if (
                !file_exists(dirname($this->accessTokenPath)) &&
                !mkdir($concurrentDirectory = dirname($this->accessTokenPath), 0700, true) &&
                !is_dir($concurrentDirectory)
            ) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            // Save the token to a file.
            file_put_contents($this->accessTokenPath, json_encode($this->googleClient->getAccessToken()));
        }
    }

    /**
     * @return \Google_Client
     * @throws \Google_Exception
     */
    private function initGoogleClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName($this->appName);
        $client->setScopes($this->scopes);
        $client->setAuthConfig($this->authConfigPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if (file_exists($this->accessTokenPath)) {
            $client->setAccessToken(file_get_contents($this->accessTokenPath));
        }

        return $client;
    }

    /**
     * Requests OAuth authorization
     *
     * @throws \ErrorException
     */
    private function requestAuthorization(): void
    {
        // Request authorization from the user.
        $authUrl = $this->googleClient->createAuthUrl();

        printf("Open the following link in your browser:\n%s\n", $authUrl);

        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $this->googleClient->fetchAccessTokenWithAuthCode($authCode);
        $this->googleClient->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new \ErrorException(implode(', ', $accessToken));
        }
    }
}