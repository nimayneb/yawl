<?php

use JayBeeR\Wildcard\WildcardConverter;
use JayBeeR\Wildcard\WildcardFactory;
use JayBeeR\Wildcard\WildcardGenerator;
use JayBeeR\Wildcard\WildcardMatcher;

require_once '../../vendor/autoload.php';
require_once 'Benchmarker.inc';

{
    $temp = [];

    foreach (WildcardGenerator::getRandomCount(1000) as $wildcards) {
        $wildcards['regExp'] = WildcardConverter::convertWildcardToRegularExpression($wildcards['wildcard']);
        $temp[] = $wildcards;
    }

    $factory = new WildcardFactory();
    $factory->setMultiByte();

    foreach ($temp as $index => ['wildcard' => $wildcard]) {
        $performer = $factory->get($wildcard);
    }

    $times = 1;

    Benchmarker::run(
        'mb_ereg_match',
        function() use($temp) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard, 'regExp' => $regExp]) {
                if (true !== mb_ereg_match($regExp, $subject)) {
                    throw new Exception(sprintf('Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>', $regExp, $subject, $index));
                }
            }
        },
        $times
    );

    $wc = new class () {
        use WildcardMatcher;

        public function __construct()
        {
            $this->setMultiByte();
        }
    };

    Benchmarker::run(
        'WildcardMatcher',
        function() use($temp, $wc) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                if (!$wc->matchWildcard($subject, $wildcard)) {
                    throw new Exception(sprintf('Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>', $wildcard, $subject, $index));
                }
            }
        },
        $times
    );

    Benchmarker::run(
        'WildcardPerformer',
        function() use($temp, $wc, $factory) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                $performer = $factory->get($wildcard);

                if (!$performer->match($subject)) {
                    throw new Exception(sprintf('Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>', $wildcard, $subject, $index));
                }
            }
        },
        $times
    );

    Benchmarker::report();

    echo "\n(" . $wc->getCachedSize() . ")\n";
}