<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

use JayBeeR\Wildcard\WildcardConverter;
use JayBeeR\Wildcard\Tests\Helper\WildcardGenerator;
use JayBeeR\Wildcard\WildcardMatcher;
use JayBeeR\Wildcard\WildcardFactory;

require_once '../../vendor/autoload.php';

{
    $temp = [];

    foreach (WildcardGenerator::getRandomCount(10000) as $wildcards) {
        $wildcards['regExp'] = WildcardConverter::convertWildcardToRegularExpression($wildcards['wildcard']);
        $temp[] = $wildcards;
    }

    $factory = new WildcardFactory;

    foreach ($temp as $index => ['wildcard' => $wildcard]) {
        $performer = $factory->get($wildcard);
    }

    $times = 1;

    \JayBeeR\Gists\Benchmark::run(
        'preg_match',
        function () use ($temp) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard, 'regExp' => $regExp]) {
                $currentResult = preg_match("/{$regExp}/", $subject);

                if (false === $currentResult) {
                    throw new Exception('Invalid pattern found!');
                }

                if (true !== (1 === $currentResult)) {
                    throw new Exception(
                        sprintf(
                            'Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>',
                            $regExp,
                            $subject,
                            $index
                        )
                    );
                }
            }
        },
        $times
    );

    $wc = new WildcardMatcher;

    \JayBeeR\Gists\Benchmark::run(
        'WildcardMatcher',
        function () use ($temp, $wc) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                if (!$wc->match($subject, $wildcard)) {
                    throw new Exception(
                        sprintf(
                            'Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>',
                            $wildcard,
                            $subject,
                            $index
                        )
                    );
                }
            }
        },
        $times
    );

    \JayBeeR\Gists\Benchmark::run(
        'WildcardPerformer',
        function() use($temp, $wc, $factory) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                $phraser = $factory->get($wildcard);

                if (!$phraser->match($subject)) {
                    throw new Exception(
                        sprintf(
                            'Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>',
                            $wildcard,
                            $subject,
                            $index
                        )
                    );
                }
            }
        },
        $times
    );

    \JayBeeR\Gists\Benchmark::report();

    echo "\n(" . $wc->getCachedSize() . ")\n";
}