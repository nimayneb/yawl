<?php

use JayBeeR\Wildcard\Encoding;
use JayBeeR\Wildcard\Tests\WildcardMatcherTest;
use JayBeeR\Wildcard\WildcardConverter;
use JayBeeR\Wildcard\WildcardMatcher;
use JayBeeR\Wildcard\WildcardPerformer;

require_once '../../vendor/autoload.php';
require_once 'Benchmarker.inc';

{
    $temp = generateRandomWildcards(10);
    $times = 1000;

    Benchmarker::run(
        'mb_ereg_match',
        function() use($temp) {
            foreach ($temp as $index => [$subject, $wildcard, $regExp]) {
                mb_ereg_match($regExp, $subject);
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
            foreach ($temp as $index => [$subject, $wildcard, $regExp]) {
                $wc->matchWildcard($subject, $wildcard);
            }
        },
        $times
    );

    /*Benchmarker::run(
        'WildcardPerformer',
        function() use($temp, $wc) {
            foreach ($temp as $index => [$subject, $wildcard, $regExp, $result]) {
                $performer = WildcardPerformer::get($wildcard, fn(Encoding $encoding) => $encoding->setMultiByte());

                if ($result !== $performer->hasMatch($subject)) {
                    throw new Exception(sprintf('Invalid result for %s found!', $index));
                }
            }
        },
        $times
    );*/

    Benchmarker::report();

    echo "\n(" . $wc->getCachedSize() . ")\n";
}