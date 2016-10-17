<?php

namespace Hmaus\Spas\Request\Result;

use GuzzleHttp\Exception\RequestException;
use Hmaus\Spas\Request\Result\Printer\Printer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ExceptionHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * List of printers to output different content-types
     *
     * Format:
     *   "content-type" => Printer
     *
     * @var Printer[]
     */
    private $printers = [];

    /**
     * @var Printer
     */
    private $universalPrinter;

    public function __construct(LoggerInterface $logger, Printer $universalPrinter)
    {
        $this->logger = $logger;
        $this->universalPrinter = $universalPrinter;
    }

    public function handle(\Exception $exception)
    {
        if ($exception instanceof RequestException) {
            $this->handleRequestException($exception);
            return;
        }

        $this->universalPrinter->printIt(
            $exception->getMessage(), LogLevel::ERROR
        );
    }

    /**
     * @param Printer $printer
     */
    public function addPrinter(Printer $printer)
    {
        $this->printers[$printer->getContentType()] = $printer;
    }

    /**
     * @param RequestException $requestException
     */
    private function handleRequestException(RequestException $requestException)
    {
        if (!$requestException->hasResponse()) {
            // response on the exception can be null, hence we must be prepared for this
            $this->universalPrinter->printIt(
                $requestException->getMessage(), LogLevel::ERROR
            );
            return;
        }

        $response = $requestException->getResponse();

        $this->logger->error(
            sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
        );

        $body = $response->getBody()->getContents();

        if (empty($body)) {
            return;
        }

        $contentType = $response->getHeaderLine('content-type');
        $printer = $this->getPrinterByContentType($contentType);
        $printer->printIt($body, LogLevel::ERROR);
    }

    /**
     * @param string $contentType
     * @return Printer
     */
    private function getPrinterByContentType(string $contentType)
    {
        if (!isset($this->printers[$contentType])) {
            return $this->universalPrinter;
        }

        return $this->printers[$contentType];
    }
}
