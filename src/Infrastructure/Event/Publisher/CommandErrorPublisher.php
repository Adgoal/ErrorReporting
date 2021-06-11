<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Infrastructure\Event\Publisher;

use AdgoalCommon\Base\Domain\Exception\LoggerException;
use AdgoalCommon\Base\Utils\LoggerTrait;
use AdgoalCommon\ErrorReporting\Application\Processor\ErrorEventProcessor;
use AdgoalCommon\ErrorReporting\Domain\Exception\EventListenerException;
use Closure;
use Enqueue\Client\ProducerInterface;
use Error;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use League\Tactician\CommandEvents\Event\CommandFailed;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Throwable;

/**
 * Class CommandErrorPublisher.
 *
 * @category Domain\Event\Publisher
 */
class CommandErrorPublisher extends AbstractListener
{
    use LoggerTrait;

    /**
     * @var ProducerInterface
     */
    private $queueProducer;

    /**
     * CommandErrorPublisher constructor.
     *
     * @param ProducerInterface $queueProducer
     */
    public function __construct(ProducerInterface $queueProducer)
    {
        $this->queueProducer = $queueProducer;
    }

    /**
     * Handle an event.
     *
     * @param EventInterface $event
     *
     * @throws EventListenerException
     * @throws ReflectionException
     * @throws LoggerException
     */
    public function handle(EventInterface $event): void
    {
        $this->logMessage('Handle error event', LOG_DEBUG);

        if (!$event instanceof CommandFailed) {
            throw new EventListenerException('Event not instance of '.CommandFailed::class);
        }
        $this->produce($event->getException());
    }

    /**
     * Send exception to queue.
     *
     * @param Throwable $exception
     *
     * @throws ReflectionException
     */
    private function produce(Throwable $exception): void
    {
        $this->flattenThrowableBacktrace($exception);
        $this->queueProducer->sendEvent(ErrorEventProcessor::QUEUE_COMMAND_FAILED_ERROR, serialize($exception));
    }

    /**
     * Resolve serialize issues in exception class.
     *
     * @param Throwable $exception
     *
     * @psalm-suppress ArgumentTypeCoercion
     *
     * @throws ReflectionException
     */
    private function flattenThrowableBacktrace(Throwable $exception): void
    {
        $flatten = static function (&$value): void {
            if ($value instanceof Closure) {
                $closureReflection = new ReflectionFunction($value);
                $value = sprintf(
                    '(Closure at %s:%s)',
                    $closureReflection->getFileName(),
                    $closureReflection->getStartLine()
                );
            } elseif (is_object($value)) {
                $value = sprintf('object(%s)', get_class($value));
            } elseif (is_resource($value)) {
                $value = sprintf('resource(%s)', get_resource_type($value));
            }
        };

        do {
            $exceptionType = $exception instanceof Error ? 'Error' : 'Exception';
            /** @psalm-suppress TypeCoercion */
            $traceProperty = (new ReflectionClass($exceptionType))->getProperty('trace');
            $traceProperty->setAccessible(true);
            $trace = $traceProperty->getValue($exception);

            /** @psalm-suppress TypeCoercion */
            $codeProperty = (new ReflectionClass($exceptionType))->getProperty('code');
            $codeProperty->setAccessible(true);
            $codeProperty->setValue($exception, (int) $codeProperty->getValue($exception));
            $codeProperty->setAccessible(false);

            foreach ($trace as &$call) {
                if (!isset($call['args']) || !is_array($call['args'])) {
                    continue;
                }
                array_walk_recursive($call['args'], $flatten);
            }
            unset($call);
            $traceProperty->setValue($exception, $trace);
        } while ($exception = $exception->getPrevious());

        $traceProperty->setAccessible(false);
    }
}
