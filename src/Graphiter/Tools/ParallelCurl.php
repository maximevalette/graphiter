<?php

namespace Graphiter\Tools;

/**
 * Class ParallelCurl
 * By Pete Warden <pete@petewarden.com>, freely reusable, see http://petewarden.typepad.com for more
 *
 * @package Graphiter\Tools
 */
class ParallelCurl
{
    public $maxRequests;
    public $options;
    public $outstandingRequests;
    public $multiHandle;

    /**
     * @param int   $inMaxRequests
     * @param array $inOptions
     */
    public function __construct($inMaxRequests = 10, $inOptions = array())
    {
        $this->maxRequests = $inMaxRequests;
        $this->options      = $inOptions;

        $this->outstandingRequests = array();
        $this->multiHandle         = curl_multi_init();
    }

    /**
     * Ensure all the requests finish nicely
     */
    public function __destruct()
    {
        $this->finishAllRequests();
    }

    /**
     * Sets how many requests can be outstanding at once before we block and wait for one to
     * finish before starting the next one
     *
     * @param int $inMaxRequests
     */
    public function setMaxRequests($inMaxRequests)
    {
        $this->maxRequests = $inMaxRequests;
    }

    /**
     * Sets the options to pass to curl, using the format of curl_setopt_array()
     *
     * @param mixed $inOptions
     */
    public function setOptions($inOptions)
    {

        $this->options = $inOptions;
    }

    /**
     * Start a fetch from the $url address, calling the $callback function passing the optional
     * $userData value. The callback should accept 3 arguments, the url, curl handle and user
     * data, eg on_request_done($url, $ch, $userData);
     *
     * @param string $url
     * @param mixed  $callback
     * @param array  $userData
     * @param null   $postFields
     */
    public function startRequest($url, $callback, $userData = array(), $postFields = null)
    {

        if ($this->maxRequests > 0) {
            $this->waitForOutstandingRequestsToDropBelow($this->maxRequests);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt_array($ch, $this->options);
        curl_setopt($ch, CURLOPT_URL, $url);

        if (isset($postFields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        curl_multi_add_handle($this->multiHandle, $ch);

        $chArrayKey = (int) $ch;

        $this->outstandingRequests[$chArrayKey] = array('url' => $url, 'callback' => $callback, 'user_data' => $userData,);

        $this->checkForCompletedRequests();
    }

    /**
     * You *MUST* call this function at the end of your script. It waits for any running requests
     * to complete, and calls their callback functions
     */
    public function finishAllRequests()
    {
        $this->waitForOutstandingRequestsToDropBelow(1);
    }

    /**
     * Checks to see if any of the outstanding requests have finished
     */
    private function checkForCompletedRequests()
    {
        /*
            // Call select to see if anything is waiting for us
            if (curl_multi_select($this->multiHandle, 0.0) === -1)
                return;

            // Since something's waiting, give curl a chance to process it
            do {
                $mrc = curl_multi_exec($this->multiHandle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            */
        // fix for https://bugs.php.net/bug.php?id=63411
        do {
            $mrc = curl_multi_exec($this->multiHandle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->multiHandle) != - 1) {
                do {
                    $mrc = curl_multi_exec($this->multiHandle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            } else {
                return;
            }
        }

        // Now grab the information about the completed requests
        while ($info = curl_multi_info_read($this->multiHandle)) {

            $ch           = $info['handle'];
            $chArrayKey = (int) $ch;

            if (!isset($this->outstandingRequests[$chArrayKey])) {
                die("Error - handle wasn't found in requests: '$ch' in " . print_r($this->outstandingRequests, true));
            }

            $request = $this->outstandingRequests[$chArrayKey];

            $url       = $request['url'];
            $content   = curl_multi_getcontent($ch);
            $callback  = $request['callback'];
            $userData = $request['user_data'];

            call_user_func($callback, $content, $url, $ch, $userData);

            unset($this->outstandingRequests[$chArrayKey]);

            curl_multi_remove_handle($this->multiHandle, $ch);
        }

    }

    /**
     * Blocks until there's less than the specified number of requests outstanding
     * 
     * @param int $max
     */
    private function waitForOutstandingRequestsToDropBelow($max)
    {
        while (1) {
            $this->checkForCompletedRequests();
            if (count($this->outstandingRequests) < $max) {
                break;
            }

            usleep(10000);
        }
    }

}