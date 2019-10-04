<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class Benchmarker
{
    protected static $results = [];

    public static function run(string $label, Closure $handler, int $times = 0)
    {
        echo sprintf('  Benchmarking <%s> ...', $label) . "\n";

        $start = microtime(true);

        for ($i = 0; $i < $times; $i++) {
            $handler();
        }

        $stop = microtime(true);

        static::$results[$label] = [
            number_format($stop - $start, '8', '.', ''),
            number_format(memory_get_peak_usage(true), 0, '', '.'),
        ];
    }

    public static function report()
    {
        echo "\n";
        echo '  ' . str_repeat('-', 88) . "\n";
        echo ' | Benchmark                       | Time           | Memory         | Time differences   |' . "\n";
        echo '  ' . str_repeat('-', 88) . "\n";

        uasort(static::$results, function($a, $b) {
            return $a[0] > $b[0] ? 1 : -1;
        });

        $previous = 0;
        $first = 0;

        foreach (static::$results as $label => $results) {
            $ratio = 100;
            $firstRatio = 0;

            if (0 === $first) {
                $first = $results[0];
            }

            if (0 !== $previous) {
                $current = $results[0];
                $ratio = 100 - (($previous / $current) * 100);
                $firstRatio = 100 - (($first / $current) * 100);
            }

            echo sprintf(' | %\' 30s: | %\' 14s | %\' 14s | %\' 5d %%    %\' 5d %% |%s', $label, $results[0], $results[1], $ratio, $firstRatio, "\n");

            $previous = $results[0];
        }

        echo '  ' . str_repeat('-', 88) . "\n";
        echo "\n";
    }
}
