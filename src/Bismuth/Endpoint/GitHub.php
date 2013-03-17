<?php

namespace Bismuth\Endpoint;

use Bismuth\Core\Api;
use Bismuth\Core\Auth\Auth;
use Bismuth\Tools\Object;

class GitHub extends Api
{

    protected $rateLimit = 5000;
    protected $rateLimitRemaining = 5000;

    /**
     * Below is the standard constructor setup for creating a new endpoint.
     *
     * To create other endpoints, use this type of setup, and then everything else is pragmatic
     */
    public function __construct(Auth $authObj)
    {
        parent::__construct($authObj);
        $this->setEndpointUrl('https://api.github.com');
        $this->setTransferType(self::TRANSFER_JSON);

        // hook into the headers to get the rate limits
        $this->addHeaderHook('X-RateLimit-Limit',       function($limit) { $this->rateLimit = $limit; });
        $this->addHeaderHook('X-RateLimit-Remaining',   function($limit) { $this->rateLimitRemaining = $limit; });
    }

    /**
     * This is where the magic happens.  We return a javascript-ish object,
     * that can be directly used via chaining.  This can get intricate, but
     * it also cuts down on space.
     */
    public function repo($repo, $owner = '')
    {
        if (!$this->checkRateLimit()) {
            throw new \Exception('Rate limit is too high, please wait and try again');
        }

        // setup our dynamic object
        $export = new Object([
            'owner' => $owner,
            'repo'  => $repo
        ]);

        // reference our current object for the exports
        $obj =& $this;

        /**
         * Get the repo information
         */
        $export->get = function() use ($obj) {
            $response = $obj->get(
                '/repos/:owner/:repo',
                array('owner' => $this->owner, 'repo' => $this->repo)
            );

            return ($response->ok) ? $response->getData() : false;
        };

        /**
         * Save the patch information
         */
        $export->edit = function($patch) use ($obj) {
            $response = $obj->patch(
                '/repos/:owner/:repo',
                array('owner' => $this->owner, 'repo' => $this->repo),
                $patch
            );

            return ($response->ok) ? true : false;
        };

        // expose our chained methods
        return $export;
    }

    /**
     * abstract the user endpoint
     */
    public function user($user = '')
    {
        if (!$this->checkRateLimit()) {
            throw new \Exception('Rate limit is too high, please wait and try again');
        }

        // setup our dynamic object
        $export = new Object([
            'user' => $user
        ]);

        // reference our current object for the exports
        $obj =& $this;

        /**
         * Get a single user
         * user(<user>)->get()
         */
        $export->get = function() use ($obj) {
            $response = $obj->get(
                '/users/:user',
                array('user' => $this->user)
            );

            return ($response->ok) ? $response->getData() : false;
        };

        /**
         * Get the current user (logged in)
         * user()->current()
         */
        $export->current = function() use ($obj) {
            $export = new Object();

            /**
             * user()->current()->get()
             */
            $export->get = function() use ($obj) {
                $response = $obj->get('/user');
                return ($response->ok) ? $response->getData() : false;
            };

            /**
             * user()->current()->emails()
             */
            $export->emails = function() use ($obj) {
                $export = new Object();

                /**
                 * user()->current()->emails()->get()
                 */
                $export->get = function() use ($obj) {
                    $response = $obj->get('/user/emails');
                    return ($response->ok) ? $response->getData() : false;
                };

                /**
                 * user()->current()->emails()->add(<email>)
                 */
                $export->add = function($email) use ($obj) {
                    $response = $obj->post(
                        '/user/emails',
                        null,
                        is_array($email) ? $email : array($email)
                    );

                    return ($response->ok) ? true : false;
                };

                /**
                 * user()->current()->emails()->delete(<email>)
                 */
                $export->delete = function($email) use ($obj) {
                    $response = $obj->delete(
                        '/user/emails',
                        null,
                        is_array($email) ? $email : array($email)
                    );

                    return ($response->ok) ? true : false;
                };

                return $export;
            };

            /**
             * Keys needs to be extended to allow for adding and deleting keys
             */
            $export->keys = function($id = null) use ($obj) {
                $keyURL = !empty($id) ? '/user/keys/:id' : '/user/keys';
                $response = $obj->get(
                    $keyURL,
                    !empty($id) ? array('id' => $id) : null
                );

                return ($response->ok) ? $response->getData() : false;
            };

            return $export;
        };

        /**
         * Get a users SSH keys
         */
        $export->keys = function() use ($obj) {
            $response = $obj->get(
                '/users/:user/keys',
                array('user' => $this->user)
            );

            return ($response->ok) ? $response->getData() : false;
        };


        return $export;
    }

    public function checkRateLimit()
    {
        return !!($this->getRateLimitRemaining() > 1);
    }

    public function getRateLimitRemaining()
    {
        return $this->rateLimitRemaining;
    }

}

?>