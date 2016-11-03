<?php

namespace Hmaus\Spas\Request\Result;

use GuzzleHttp\Exception\RequestException;
use Hmaus\Spas\Formatter\FormatterService;
use Psr\Log\LoggerInterface;

class ExceptionHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormatterService
     */
    private $formatterService;

    public function __construct(LoggerInterface $logger, FormatterService $formatterService)
    {
        $this->logger = $logger;
        $this->formatterService = $formatterService;
    }

    public function handle(\Exception $exception)
    {
        if ($exception instanceof RequestException) {
            $this->handleRequestException($exception);
            return;
        }

        $this->logger->error($exception->getMessage());
    }

    /**
     * @param RequestException $requestException
     */
    private function handleRequestException(RequestException $requestException)
    {
        if (!$requestException->hasResponse()) {
            $this->logger->error($requestException->getMessage());
            return;
        }

        $response = $requestException->getResponse();

        $this->logger->error(
            '{0} {1}', [$response->getStatusCode(), $response->getReasonPhrase()]
        );

        $body = $response->getBody()->getContents();

        if (empty($body)) {
            return;
        }

        $formatter = $this
            ->formatterService
            ->getFormatterByContentType(
                $response->getHeaderLine('content-type')
            );

        $this->logger->error($formatter->format($body));
    }
}
