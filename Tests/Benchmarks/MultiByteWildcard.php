<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

use JayBeeR\Wildcard\WildcardConverter;
use JayBeeR\Wildcard\WildcardFactory;
use JayBeeR\Wildcard\Tests\Helper\WildcardGenerator;
use JayBeeR\Wildcard\WildcardMatcher;

require_once '../../vendor/autoload.php';

{
    $temp = [];

    foreach (WildcardGenerator::getRandomCount(10000) as $wildcards) {
        $wildcards['regExp'] = WildcardConverter::convertWildcardToRegularExpression($wildcards['wildcard']);
        $temp[] = $wildcards;
    }

    $factory = new WildcardFactory();
    $factory->setMultiByte();

    foreach ($temp as $index => ['wildcard' => $wildcard]) {
        $performer = $factory->get($wildcard);
    }

    $times = 1;

    \JayBeeR\Gists\Benchmark::run(
        'mb_ereg_match',
        function () use ($temp) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard, 'regExp' => $regExp]) {
                if (true !== mb_ereg_match($regExp, $subject)) {
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
    $wc->setMultiByte();

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
        function () use ($temp, $wc, $factory) {
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