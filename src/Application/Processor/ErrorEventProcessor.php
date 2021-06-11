<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Application\Processor;

use AdgoalCommon\Base\Domain\Exception\ClassNotAllowedToUnserializeException;
use AdgoalCommon\Base\Domain\Exception\LoggerException;
use AdgoalCommon\Base\Utils\LoggerTrait;
use AdgoalCommon\ErrorReporting\Domain\Repository\Processor\ErrorReportingProcessorInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Throwable;

/**
 * Class ErrorEventConsumer.
 *
 * @category Domain\Event\Consumer
 */
class ErrorEventProcessor implements Processor, TopicSubscriberInterface
{
    use LoggerTrait;

    public const QUEUE_COMMAND_FAILED_ERROR = 'log.command.failed.error';

    /**
     * @var ErrorReportingProcessorInterface
     */
    private $exceptionProcessor;

    /**
     * ErrorEventConsumer constructor.
     *
     * @param ErrorReportingProcessorInterface $exceptionProcessor
     */
    public function __construct(ErrorReportingProcessorInterface $exceptionProcessor)
    {
        $this->exceptionProcessor = $exceptionProcessor;
    }

    /**
     * Process enqueue message.
     *
     * @param Message $message
     * @param Context $context
     *
     * @return object|string
     *
     * @throws LoggerException
     */
    public function process(Message $message, Context $context)
    {
        $serializedException = $message->getBody();
        $this->logMessage('Consume error event', LOG_DEBUG);

        try {
            $exception = unserialize($serializedException);

            if (!$exception instanceof Throwable) {
                throw new ClassNotAllowedToUnserializeException(sprintf('Not allowed class: %s', get_class($exception)));
            }
        } catch (Throwable $exception) {
            $this->logMessage(sprintf('Consume error event with Exception: %s, %s', get_class($exception), $exception->getMessage()), LOG_DEBUG);
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        $this->exceptionProcessor->handleException($exception);

        return self::ACK;
    }

    /**
     * Return enqueue command routers.
     *
     * @return string
     */
    public static function getSubscribedTopics(): string
    {
        return self::QUEUE_COMMAND_FAILED_ERROR;
    }
}
