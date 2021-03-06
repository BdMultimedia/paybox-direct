<?php

/*
 * This file is part of the Nexylan packages.
 *
 * (c) Nexylan SAS <contact@nexylan.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nexy\PayboxDirect\HttpClient;

use Nexy\PayboxDirect\Exception\PayboxException;
use Nexy\PayboxDirect\Paybox;
use Nexy\PayboxDirect\Response\ResponseInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 *
 * @see http://www1.paybox.com/espace-integrateur-documentation/les-solutions-paybox-direct-et-paybox-direct-plus/
 */
abstract class AbstractHttpClient
{
    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var int
     */
    protected $baseUrl = Paybox::API_URL_TEST;

    /**
     * @var string[]
     */
    private $baseParameters;

    /**
     * @var int
     */
    private $defaultCurrency;

    /**
     * @var int|null
     */
    private $defaultActivity = null;

    /**
     * @var int
     */
    private $questionNumber;

    /**
     * Constructor.
     */
    final public function __construct()
    {
        $this->questionNumber = mt_rand(1, 2000000000);
    }

    /**
     * @param array $options
     */
    final public function setOptions($options)
    {
        $this->timeout = $options['timeout'];
        $this->baseUrl = true === $options['production'] ? Paybox::API_URL_PRODUCTION : Paybox::API_URL_TEST;
        $this->baseParameters = [
            'VERSION' => $options['paybox_version'],
            'SITE' => $options['paybox_site'],
            'RANG' => $options['paybox_rank'],
            'IDENTIFIANT' => $options['paybox_identifier'],
            'CLE' => $options['paybox_key'],
        ];
        $this->defaultCurrency = $options['paybox_default_currency'];
        if (array_key_exists('paybox_default_activity', $options)) {
            $this->defaultActivity = $options['paybox_default_activity'];
        }
    }

    /**
     * Calls PayBox Direct platform with given operation type and parameters.
     *
     * @param int      $type          Request type
     * @param string[] $parameters    Request parameters
     * @param string   $responseClass
     *
     * @return ResponseInterface The response content
     *
     * @throws PayboxException
     */
    final public function call($type, array $parameters, $responseClass)
    {
        if (!in_array(ResponseInterface::class, class_implements($responseClass))) {
            throw new \InvalidArgumentException('The response class must implement '.ResponseInterface::class.'.');
        }

        $bodyParams = $this->getParameters($type, $parameters);
        $bodyParams['DATEQ'] = null !== $parameters['DATEQ'] ? $parameters['DATEQ'] : date('dmYHis');

        $response = $this->request($bodyParams);
        $results = self::parseHttpResponse($response);

        $this->questionNumber = (int) $results['NUMQUESTION'] + 1;

        /** @var ResponseInterface $response */
        $response = new $responseClass($results);

        if (!$response->isSuccessful()) {
            throw new PayboxException($response);
        }

        return $response;
    }

    /**
     * Get parameters specified for request
     *
     * @param $type
     * @param array $parameters
     *
     * @return array
     */
    final public function getParameters($type, array $parameters)
    {
        $bodyParams = array_merge($parameters, $this->baseParameters);
        $bodyParams['TYPE'] = $type;
        $bodyParams['NUMQUESTION'] = $this->questionNumber;
        // Restore default_currency from parameters if given
        if (array_key_exists('DEVISE', $parameters)) {
            $bodyParams['DEVISE'] = null !== $parameters['DEVISE'] ? $parameters['DEVISE'] : $this->defaultCurrency;
        }
        if (!array_key_exists('ACTIVITE', $parameters) && $this->defaultActivity) {
            $bodyParams['ACTIVITE'] = $this->defaultActivity;
        }

        // `ACTIVITE` must be a string of 3 numbers to get it working with Paybox API.
        if (array_key_exists('ACTIVITE', $bodyParams)) {
            $bodyParams['ACTIVITE'] = str_pad($bodyParams['ACTIVITE'], 3, '0', STR_PAD_LEFT);
        }

        return $bodyParams;
    }

    /**
     * Generate results array from HTTP response body
     *
     * @param string $response
     * @return array
     */
    final public static function parseHttpResponse($response)
    {
        $results = [];
        foreach (explode('&', $response) as $element) {
            list($key, $value) = explode('=', $element);
            $value = utf8_encode(trim($value));
            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * Init and setup http client with PayboxDirectPlus SDK options.
     */
    abstract public function init();

    /**
     * Sends a request to the server, receive a response and returns it as a string.
     *
     * @param string[] $parameters Request parameters
     *
     * @return string The response content
     */
    abstract protected function request($parameters);
}
