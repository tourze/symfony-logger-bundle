<?php

namespace Tourze\SymfonyLoggerBundle\Service;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Tourze\Arrayable\Arrayable;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\AsyncContracts\AsyncMessageInterface;
use Tourze\BacktraceHelper\ContextAwareInterface;
use Tourze\BacktraceHelper\ExceptionPrinter;
use Tourze\BacktraceHelper\LogDataInterface;

readonly class ContextFormatterService
{
    public function __construct(private NormalizerInterface $objectNormalizer)
    {
    }

    public function formatLine(mixed $v): mixed
    {
        if ($v instanceof \JsonSerializable) {
            return $v->jsonSerialize();
        }

        if ($v instanceof LogDataInterface) {
            return $this->formatLogData($v);
        }

        if ($v instanceof AsyncMessageInterface) {
            return $this->formatAsyncMessage($v);
        }

        if ($v instanceof PlainArrayInterface) {
            return $this->formatPlainArray($v);
        }

        if ($v instanceof Arrayable) {
            return $this->formatArrayable($v);
        }

        if ($v instanceof OptimisticLockException) {
            return $this->formatOptimisticLockException($v);
        }

        if ($v instanceof \Throwable) {
            return $this->formatThrowable($v);
        }

        return $v;
    }

    private function formatLogData(LogDataInterface $v): mixed
    {
        try {
            return $v->generateLogData();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAsyncMessage(AsyncMessageInterface $v): array
    {
        try {
            $arr = $this->objectNormalizer->normalize($v, 'array');
            if (is_array($arr)) {
                $arr['_class'] = get_class($v);

                return $arr;
            }

            return $this->createErrorArray($v, new \Exception('Normalizer did not return array'));
        } catch (\Throwable $e) {
            return $this->createErrorArray($v, $e);
        }
    }

    /**
     * @param PlainArrayInterface<string, mixed> $v
     * @return array<string, mixed>
     */
    private function formatPlainArray(PlainArrayInterface $v): array
    {
        try {
            return $v->retrievePlainArray();
        } catch (\Throwable $e) {
            return $this->createErrorArray($v, $e);
        }
    }

    /**
     * @param Arrayable<string, mixed> $v
     * @return array<string, mixed>
     */
    private function formatArrayable(Arrayable $v): array
    {
        try {
            $record = $v->toArray();
            $record['_class'] = $v::class;

            return $record;
        } catch (\Throwable $e) {
            return $this->createErrorArray($v, $e);
        }
    }

    private function formatOptimisticLockException(OptimisticLockException $v): string
    {
        $entity = $v->getEntity();
        if ($entity instanceof \Stringable) {
            $entity = strval($entity);
            $exception = ExceptionPrinter::exception($v);

            return "处理实体[{$entity}]时发生乐观锁异常，异常调用栈：{$exception}";
        }

        return ExceptionPrinter::exception($v);
    }

    private function formatThrowable(\Throwable $v): mixed
    {
        $formattedException = ExceptionPrinter::exception($v);

        if ($v instanceof NotNormalizableValueException) {
            return $this->formatNotNormalizableValueException($v, $formattedException);
        }

        if ($v instanceof ContextAwareInterface) {
            return $this->formatContextAwareException($v, $formattedException);
        }

        return $formattedException;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNotNormalizableValueException(
        NotNormalizableValueException $v,
        string $formattedException,
    ): array {
        return [
            'exception' => $formattedException,
            'currentType' => $v->getCurrentType(),
            'expectedTypes' => $v->getExpectedTypes(),
            'path' => $v->getPath(),
            'useMessageForUser' => $v->canUseMessageForUser(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatContextAwareException(
        ContextAwareInterface $v,
        string $formattedException,
    ): array {
        $context = [];
        foreach ($v->getContext() as $k => $value) {
            $context[$k] = $this->formatLine($value);
        }

        return [
            'exception' => $formattedException,
            'context' => $context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createErrorArray(object $v, \Throwable $e): array
    {
        return [
            '_class' => get_class($v),
            '_formatException' => $e->getMessage(),
        ];
    }
}
