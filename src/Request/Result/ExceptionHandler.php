<?php

namespace Hmaus\Spas\Request\Result;

use GuzzleHttp\Exception\RequestException;
use Hmaus\Spas\Request\Result\Printer\JsonPrinter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ExceptionHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(\Exception $exception)
    {
        if ($exception instanceof RequestException) {
            $this->handleRequestException($exception);
            return;
        }

        // todo think of something better than justing printing the message
        $this->logger->error($exception->getMessage());
    }

    /**
     * @param RequestException $requestException
     */
    private function handleRequestException(RequestException $requestException)
    {
        $response = $requestException->getResponse();

        if (!$response) {
            $this->logger->error($requestException->getMessage());

            return;
        }

        $this->logger->error(
            sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
        );

        $body = $response->getBody()->getContents();

        if (!$body) {
            return;
        }

        $contentType = $response->getHeaderLine('content-type');

        if (!$contentType) {
            return;
        }

        if (strpos($contentType, 'json') !== false) {
            $printer = new JsonPrinter($this->logger);
            $printer->printIt($body, LogLevel::ERROR);

            return;
        }

        // todo add support for other content types as well
    }
}
