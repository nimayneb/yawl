<?php

use JayBeeR\Wildcard\WildcardConverter;
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

    $times = 1;

    Benchmarker::run(
        'preg_match',
        function () use ($temp) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard, 'regExp' => $regExp]) {
                $currentResult = preg_match("/{$regExp}/", $subject);

                if (false === $currentResult) {
                    throw new Exception('Invalid pattern found!');
                }

                if (true !== (1 === $currentResult)) {
                    throw new Exception(sprintf('Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>', $regExp, $subject, $index));
                }
            }
        },
        $times
    );

    $wc = new class ()
    {
        use WildcardMatcher;

        public function __construct()
        {
            $this->setSingleByte();
        }
    };

    Benchmarker::run(
        'WildcardMatcher',
        function () use ($temp, $wc) {
            foreach ($temp as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                if (!$wc->matchWildcard($subject, $wildcard)) {
                    throw new Exception(sprintf('Invalid result for <%s> (%s) [%s] found! expected: <true>, but <false>', $wildcard, $subject, $index));
                }
            }
        },
        $times
    );

    /*Benchmarker::run(
        'WildcardPerformer',
        function() use($temp, $wc) {
            foreach ($temp as $index => [$subject, $wildcard, $regExp, $result]) {
                $performer = WildcardPerformer::get($wildcard, fn(Encoding $encoding) => $encoding->setSingleByte());

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