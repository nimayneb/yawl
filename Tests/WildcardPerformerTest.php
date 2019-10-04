<?php namespace JayBeeR\Wildcard\Tests {

    use JayBeeR\Wildcard\Encoding;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\WildcardPerformer;
    use PHPUnit\Framework\TestCase;

    class WildcardPerformerTest extends TestCase
    {
        /**
         * @return array
         */
        public function wildcardVariantsProvider()
        {
            return [
                ['s', '?', true],
                ['s', '*', true],
                ['s', '??', false],
                ['ss', '*', true],
                ['', '*', true],
                ['', '**', false],

                ['search phrase', 'search**phrase', true],
                ['search phrase', 'search*phrase', true],
                ['searchphrase', 'search**phrase', false],
                ['searchphrase', 'search*phrase', true],
                ['search phrase', 'search phrase?*', true],
                ['search phrases', 'search phrase?*', true],
                ['search phrasess', 'search phrase?*', false],

                ['search phrase', '*', true],
                ['search phrase', '*phrase', true],
                ['search phrase', 'search*', true],
                ['search phrase', '*h p*', true],
                ['search phrase', '*h?p*', true],
                ['search phrase', '*h*p*', true],
                ['search phrase', 'search?phrase', true],
                ['search phrase', '?????? phrase', true],

                ['search phrase', '?', false],
                ['search phrase', '????? phrase', false],
                ['search phrase', '*search', false],
                ['search phrase', 'false phrase', false],
                ['search phrase', 'false*', false],
                ['search phrase', '*false', false],
                ['search phrase', 'false?', false],
                ['search phrase', '?false', false],
                ['search phrase', '?fa?s?', false],

                ['search phrase', '?earch phra*', true],
                ['search phrase', '?ea?ch*ph?a*e', true],

                ['search phrase', '??earch phra*', false],
                ['search phrase', '?ea??ch*ph?a*e', false],

                // Escape characters
                ['?', '\\?', true],
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
                ['search\\phrase', '*\\\\\\phrase', false],
            ];
        }

        /**
         * @return array
         */
        public function wildcardVariantsWithExceptionsProvider()
        {
            return [
                ['search phrase', '***'],
                ['search phrase', '?**'],
                ['search phrase', '?*?'],
                ['search phrase', '*?']
            ];
        }

        /**
         * @param string $subject
         * @param string $pattern
         * @param bool $valid
         *
         * @throws InvalidCharacterForWildcardPattern
         *
         * @dataProvider wildcardVariantsProvider
         * @test
         */
        public function hasMultiByteWildcardMatchReturnsResultForMatchingWildcard(string $subject, string $pattern, bool $valid)
        {
            $performer = WildcardPerformer::get($pattern, fn(Encoding $performer) => $performer->setMultiByte());

            $result = $performer->hasMatch($subject);
            $this->assertEquals($valid, $result);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         *
         * @dataProvider wildcardVariantsWithExceptionsProvider
         * @test
         */
        public function hasMultiByteWildcardMatchThrowsException(string $subject, string $pattern)
        {
            $this->expectException(InvalidCharacterForWildcardPattern::class);
            WildcardPerformer::get($pattern, fn(Encoding $performer) => $performer->setMultiByte());
        }
    }
}