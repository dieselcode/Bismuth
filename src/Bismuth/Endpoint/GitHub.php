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
    public function __construct(Auth $authObj, $cacheObj = null)
    {
        parent::__construct($authObj, $cacheObj);

        $this->setEndpointUrl('https://api.github.com');
        $this->setTransferType(self::TRANSFER_JSON);
        $this->setReturnStyle(self::RETURN_JSON_OBJECT);

        // add support for the custom Github media type
        $this->addHeader('Accept', 'application/vnd.github.beta+json');

        // hook into the headers to get the rate limits
        $this->addHeaderHook('X-RateLimit-Limit',       function($limit) { $this->rateLimit = $limit; });
        $this->addHeaderHook('X-RateLimit-Remaining',   function($limit) { $this->rateLimitRemaining = $limit; });
    }

    /**
     * Abstract the orgs endpoint
     */
    public function orgs($org = '')
    {
        if (!$this->checkRateLimit()) {
            throw new \Exception('Rate limit is too high, please wait and try again');
        }

        $export = new Object(['org' => $org]);
        $obj    =& $this;

        /**
         * orgs(<org>)->get()
         */
        $export->get = function() use ($obj) {
            $response = $obj->get(
                '/orgs/:org',
                array('org' => $this->org)
            );

            return ($response->ok) ? $response->getData() : false;
        };

        /**
         * orgs(<org>)->edit(<patch)
         */
        $export->edit = function($patch) use ($obj) {
            $response = $obj->patch('/orgs/:org', null, $patch);
            return ($response->ok) ? true : false;
        };

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
        $export = new Object(['user' => $user]);
        $obj    =& $this;

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
         * User Organizations
         * user(<user>)->orgs()
         */
        $export->orgs = function() use ($obj) {
            $export = new Object();

            /**
             * user(<user>)->orgs()->list()
             */
            $export->list = function() use ($obj) {
                $response = $obj->get(
                    '/users/:user/orgs',
                    array('user' => $this->user)
                );

                return ($response->ok) ? $response->getData() : false;
            };

            return $export;
        };

        /**
         * User Gists
         * user(<user>)->gists()
         */
        $export->gists = function() use($obj, $export) {
            // thisis a horrible hack, but it works
            $export = new Object(['user' => $export->user]);

            /**
             * user(<user>)->gists()->list(<since>)
             */
            $export->list = function($since = '') use($obj) {
                $response = $obj->get(
                    '/users/:user/gists',
                    array('user' => $this->user),
                    !empty($since) ? array('since' => $since) : ''
                );

                return ($response->ok) ? $response->getData() : false;
            };

            return $export;
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
             * user()->current()->repos()
             */
            $export->repos = function() use ($obj) {
                $export = new Object();

                /**
                 * user()->current()->repos()->list(<options>)
                 */
                $export->list = function($options = array()) use ($obj) {
                    $response = $obj->get('/user/repos', $options);
                    return ($response->ok) ? $response->getData() : false;
                };

                return $export;
            };

            /**
             * user()->current()->orgs()
             */
            $export->orgs = function() use ($obj) {
                $export = new Object();

                /**
                 * user()->current()->orgs()->list()
                 */
                $export->list = function() use ($obj) {
                    $response = $obj->get('/user/orgs');
                    return ($response->ok) ? $response->getData() : false;
                };

                return $export;
            };

            /**
             * user()->current()->emails()
             */
            $export->emails = function() use ($obj) {
                $export = new Object();

                /**
                 * user()->current()->emails()->list()
                 */
                $export->list = function() use ($obj) {
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


            return $export;
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