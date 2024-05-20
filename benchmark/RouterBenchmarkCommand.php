<?php

namespace Bermuda\Router\Benchmark;

use Bermuda\Benchmark\Benchmarker;
use Console\Commands\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Benchmark\RouterBenchmark;

final class RouterBenchmarkCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pb = $this->getProgressBar($output);
        $onTick = static function () use ($pb): void {
            $pb->advance();
        };

        $pb->start();

        $result = (new Benchmarker)->setTickHandler($onTick)->bench(new RouterBenchmark);
        $rows = [[
            RouterBenchmark::class,
            1001,
            'disable',
            $result->executionTime,
            $result->iterations,
            $result->memoryUsage->toString(),
            $result->memoryPeakUsage->toString(),
        ]];

        $headers = [
            'benchmark', 'registered_routes','cache_mode',
            'exec_time', 'its', 'memory_usage', 'memory_peak_usage'
        ];

        $result = (new Benchmarker)->setTickHandler($onTick)->bench(new RouterBenchmark(true));
        $rows[] = [
            RouterBenchmark::class,
            1001,
            'enable',
            $result->executionTime,
            $result->iterations,
            $result->memoryUsage->toString(),
            $result->memoryPeakUsage->toString(),
        ];

        $pb->clear();

        (new Table($output))->setHeaders($headers)
            ->setRows($rows)
            ->render();

        return self::SUCCESS;
    }

    public function getName(): string
    {
        return 'router:bench';
    }

    public function getDescription(): string
    {
        return 'Benchmark current router implementations';
    }

    private function getProgressBar(OutputInterface $output): ProgressBar
    {
        $progressBar = new ProgressBar($output, 20000);
        $progressBar->setBarCharacter('<fg=green>⚬</>');
        $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
        $progressBar->setProgressCharacter("<fg=green>➤</>");
        $progressBar->setFormat("%current%/%max% [%bar%] %percent:3s%%\n  %estimated:-6s%  %memory:6s%");

        return $progressBar;
    }
}
