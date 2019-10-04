<?php

use JayBeeR\Wildcard\WildcardMatcher;
use JayBeeR\Wildcard\WildcardPerformer;

require_once '../../vendor/autoload.php';
require_once 'Benchmarker.inc';

{
    $temp = [
        ['s',               '?',                '^.$',                    true],
        ['s',               '*',                '^.*$',                   true],
        ['s',               '??',               '^..$',                   false],
        ['ss',              '*',                '^.*$',                   true],
        ['',                '*',                '^.*$',                   true],
        ['',                '**',               '^.{1,}$',                false],

        ['search phrase',   'search**phrase',   '^search.{1,}phrase$',    true],
        ['search phrase',   'search*phrase',    '^search.*phrase$',       true],
        ['searchphrase',    'search**phrase',   '^search.{1,}phrase$',    false],
        ['searchphrase',    'search*phrase',    '^search.*phrase$',       true],
        ['search phrase',   'search phrase?*',  '^search phrase.?$',      true],
        ['search phrases',  'search phrase?*',  '^search phrase.?$',      true],
        ['search phrasess', 'search phrase?*',  '^search phrase.?$',      false],

        ['search phrase',   '*',                '^.*$',                   true],
        ['search phrase',   '*phrase',          '^.*phrase$',             true],
        ['search phrase',   'search*',          '^search.*$',             true],
        ['search phrase',   '*h p*',            '^.*h p.*$',              true],
        ['search phrase',   '*h?p*',            '^.*h.?p.*$',             true],
        ['search phrase',   '*h*p*',            '^.*h.*p.*$',             true],
        ['search phrase',   'search?phrase',    '^search.phrase$',        true],
        ['search phrase',   '?????? phrase',    '^...... phrase$',        true],

        ['search phrase',   '?',                '^.$',                    false],
        ['search phrase',   '????? phrase',     '^..... phrase$',         false],
        ['search phrase',   '*search',          '^.*search$',             false],
        ['search phrase',   'false phrase',     '^false phrase$',         false],
        ['search phrase',   'false*',           '^false.*$',              false],
        ['search phrase',   '*false',           '^.*false$',              false],
        ['search phrase',   'false?',           '^false.$',               false],
        ['search phrase',   '?false',           '^.false$',               false],
        ['search phrase',   '?fa?s?',           '^.fa.s.$',               false],

        ['search phrase',   '?earch phra*',     '^.earch phra.*$',        true],
        ['search phrase',   '?ea?ch*ph?a*e',    '^.ea.ch.*ph.a.*e$',      true],

        ['search phrase',   '??earch phra*',    '^..earch phra.*$',       false],
        ['search phrase',   '?ea??ch*ph?a*e',   '^.ea..ch.*ph.a.*e$',     false],

        // Escape characters
        /*['?', '\\?', true],
        ['*', '\\*', true],
        ['\\', '\\', true],
        ['\\a', '\\a', true],
        ['\\a', '\\\\a', true],
        ['\\', '\\\\', true],
        ['search phrase?', '*\\?', true],
        ['?search phrase', '\\?*', true],
        ['search\\phrase', '*\\\\*', true],
        ['search\\*', '*\\\\\\*', true],
        ['search\\phrase', '*\\phrase', true],
        ['search\\phrase', 'search\\p*', true],
        ['search\\phrase', 'search\\p?', false],
        ['search\\phrase', '*\\\\phrase', true],
        ['search\\phrase', '*\\\\\\phrase', false],*/
    ];

    $times = 1;

    Benchmarker::run(
        'mb_ereg_match',
        function() use($temp) {
            foreach ($temp as $index => [$subject, $wildcard, $regExp, $result]) {
                $currentResult = mb_ereg_match($regExp, $subject);

                if ($result !== $currentResult) {
                   throw new Exception(sprintf('Invalid result for %s found!', $index));
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
            foreach ($temp as $index => [$subject, $wildcard, $regExp, $result]) {
                if ($result !== $wc->hasWildcardMatch($subject, $wildcard)) {
                    throw new Exception(sprintf('Invalid result for %s found!', $index));
                }
            }
        },
        $times
    );

    Benchmarker::run(
        'WildcardPerformer',
        function() use($temp, $wc) {
            foreach ($temp as $index => [$subject, $wildcard, $regExp, $result]) {
                $performer = WildcardPerformer::get($wildcard);
                $performer->setMultiByte();

                if ($result !== $performer->hasMatch($subject)) {
                    //throw new Exception(sprintf('Invalid result for %s found!', $index));
                }
            }
        },
        $times
    );

    Benchmarker::report();
}