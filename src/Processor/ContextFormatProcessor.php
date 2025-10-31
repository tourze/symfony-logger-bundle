<?php

namespace Tourze\SymfonyLoggerBundle\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Tourze\SymfonyLoggerBundle\Service\ContextFormatterService;

/**
 * 在文本日志中，Exception会被格式化成很简单的对象，这明显是不符合我们想法的，我们需要将其格式化为更加完整的字符串或对象。
 * 同时还有一些对象数据，我们也需要格式化一次
 */
#[AutoconfigureTag(name: 'monolog.processor')]
readonly class ContextFormatProcessor implements ProcessorInterface
{
    public function __construct(private ContextFormatterService $contextFormatterService)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if ([] === $record->context) {
            return $record;
        }

        $context = [];
        foreach ($record->context as $k => $v) {
            $context[$k] = $this->contextFormatterService->formatLine($v);
        }

        return $record->with(context: $context);
    }
}
