<?php

namespace Nexy\PayboxDirect\Tests\Request;

use Nexy\PayboxDirect\Request\AbstractNumberedReferencedTransactionRequest;
use Nexy\PayboxDirect\Response\PayboxResponse;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractNumberedReferencedTransactionRequestTest extends AbstractTransactionRequestTest
{
    public function testCallDefault()
    {
        $response = $this->getPreviousResponse(42000);

        /** @var AbstractNumberedReferencedTransactionRequest $requestClass */
        $requestClass = $this->getRequestClass();
        $request = new $requestClass(
            $this->generateReference(),
            42000,
            $response->getTransactionNumber(),
            $response->getCallNumber()
        );
        $response = $this->paybox->request($request);

        $this->assertSame(0, $response->getCode(), $response->getComment());
    }

    /**
     * {@inheritdoc}
     */
    final protected function createBaseRequest()
    {
        $response = $this->getPreviousResponse(42042);

        /** @var AbstractNumberedReferencedTransactionRequest $requestClass */
        $requestClass = $this->getRequestClass();
        $request = new $requestClass(
            $this->generateReference(),
            42042,
            $response->getTransactionNumber(),
            $response->getCallNumber()
        );

        return $request;
    }

    /**
     * @param int $amount
     *
     * @return PayboxResponse
     */
    abstract protected function getPreviousResponse($amount);
}