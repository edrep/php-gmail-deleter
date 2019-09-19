<?php namespace Edrep\Gmail;

use Edrep\Google\ClientHandler;
use Edrep\Utils;

class Deleter
{
    private const APP_NAME = 'Gmail Deleter';
    private const SNIPPETS_PEEK_COUNT = 5;
    private const MAX_RESULTS = 500; // Max allowed: 500
    const MAX_DELETE_RETRIES = 3;

    /**
     * @var \Google_Service_Gmail
     */
    private $service;

    /**
     * @param string $authConfigPath credentials.json path
     * @param string $accessTokenPath token.json path
     * @throws \Exception
     */
    public function __construct($authConfigPath, $accessTokenPath)
    {
        // Get the API client and construct the service object.
        $client = (new ClientHandler(
            self::APP_NAME,
            $authConfigPath,
            $accessTokenPath,
            \Google_Service_Gmail::MAIL_GOOGLE_COM
        ))->getGoogleClient();

        $this->service = new \Google_Service_Gmail($client);
    }

    /**
     * @return bool
     */
    public function deleteTrash(): bool
    {
        $userId = 'me';

        $profile = $this->service->users->getProfile($userId);

        Utils::confirmOrAbort(sprintf(
            "You are logged in as: %s. Type 'yes' to continue: ",
            $profile->getEmailAddress()
        ));

        $threadSearchParams = [
            'includeSpamTrash' => true,
            'q' => 'in:trash',
            'maxResults' => self::MAX_RESULTS
        ];

        $results = $this->service->users_threads->listUsersThreads($userId, $threadSearchParams);

        if (count($results->getThreads()) === 0) {
            print "No threads found in Trash.\n";

            return false;
        }

        printf("Found ~%d (estimated) threads.\n", $results->getResultSizeEstimate());

        $threadSneakPeek = $this->buildSneakPeek($results);

        print "Here's a sneak peek: \n";

        foreach ($threadSneakPeek as $threadSnippet) {
            printf("- %s\n", $threadSnippet);
        }

        Utils::confirmOrAbort(sprintf(
            "Are you sure you want to do delete all (~%d) of these threads?  Type 'yes' to continue: ",
            $results->getResultSizeEstimate()
        ));

        $threadsDeletedCount = 0;

        do {
            $threadNum           = 1;

            if (isset($nextPageToken)) {
                $threadSearchParams['pageToken'] = $nextPageToken;

                $results = $this->service->users_threads->listUsersThreads($userId, $threadSearchParams);
            }

            /**
             * @var $thread \Google_Service_Gmail_Thread
             */
            foreach ($results->getThreads() as $thread) {
                if ($this->deleteThread($userId, $thread)) {
                    $threadsDeletedCount++;
                    $status = 'Deleted';
                } else {
                    $status = 'FAILED to delete';
                }

                printf(
                    "%s %s thread %d of ~%d (Total deleted: %d): #%s\n",
                    date('[Y-m-d H:i:s]'),
                    $status,
                    $threadNum,
                    $results->getResultSizeEstimate(),
                    $threadsDeletedCount,
                    $thread->getId()
                );

                $threadNum++;
            }

            $nextPageToken = $results->getNextPageToken();
        } while (!empty($nextPageToken));

        printf("%s Deleted %d threads\n", date('[Y-m-d H:i:s]'), $threadsDeletedCount);

        return true;
    }

    /**
     * @param \Google_Service_Gmail_ListThreadsResponse $results
     * @return array
     */
    private function buildSneakPeek(\Google_Service_Gmail_ListThreadsResponse $results): array
    {
        $threadSnippetsPeek = [];

        /**
         * @var \Google_Service_Gmail_Thread $thread
         */
        foreach ($results->getThreads() as $thread) {
            if (count($threadSnippetsPeek) < self::SNIPPETS_PEEK_COUNT) {
                $threadSnippetsPeek[] = $thread->getSnippet();
            } else {
                break;
            }
        }

        return $threadSnippetsPeek;
    }

    /**
     * @param string $userId
     * @param \Google_Service_Gmail_Thread $thread
     * @return bool
     */
    private function deleteThread(
        string $userId,
        \Google_Service_Gmail_Thread $thread
    ): bool {
        $retries = 0;

        do {
            try {
                $this->service->users_threads->delete($userId, $thread->getId());
                $deleted = true;
            } catch (\Exception $e) {
                usleep(1e6);
                $retries++;
                $deleted = false;
            }
        } while (!$deleted && $retries < self::MAX_DELETE_RETRIES);

        return $deleted;
    }
}