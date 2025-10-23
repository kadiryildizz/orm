<?php
namespace ORM;

class Logger
{
    protected string $file;

    public function __construct(string $file = __DIR__ . '/storage/logs/app.log')
    {
        $this->file = $file;
        // ensure directory exists
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public function error(string $message, array $context = []): void
    {
        $time = date('Y-m-d H:i:s');
        $ctx = $context ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[$time] ERROR: $message$ctx" . PHP_EOL;
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []): void
    {
        $time = date('Y-m-d H:i:s');
        $ctx = $context ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[$time] INFO: $message$ctx" . PHP_EOL;
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}
