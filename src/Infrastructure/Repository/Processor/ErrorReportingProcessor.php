<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Infrastructure\Repository\Processor;

use AdgoalCommon\Base\Domain\Exception\LoggerException;
use AdgoalCommon\Base\Utils\LoggerTrait;
use AdgoalCommon\ErrorReporting\Domain\Repository\Processor\ErrorReportingProcessorInterface;
use Throwable;

/**
 * Interface ExceptionProcessor.
 *
 * @category Exception
 */
class ErrorReportingProcessor implements ErrorReportingProcessorInterface
{
    use LoggerTrait;

    /**
     * Handle exception.
     *
     * @param Throwable $exception
     *
     * @throws LoggerException
     */
    final public function handleException(Throwable $exception): void
    {
        $exceptionMessage = $this->getExceptionMessage($exception);
        $level = $this->getExceptionLevel($exception);
        $this->logException($exception, $level, $exceptionMessage);
    }
}
