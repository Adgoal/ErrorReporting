<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Domain\Repository\Processor;

use Throwable;

/**
 * Interface ExceptionProcessor.
 *
 * @category Exception
 */
interface ErrorReportingProcessorInterface
{
    /**
     * Handle exception.
     *
     * @param Throwable $exception
     */
    public function handleException(Throwable $exception): void;
}
