<?php namespace JayBeeR\Wildcard\Tests {

    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Failures\InvalidEscapedCharacterForWildcardPattern;
    use JayBeeR\Wildcard\WildcardMatcher;
    use PHPUnit\Framework\TestCase;

    class WildcardMatcherTest extends TestCase
    {
        /**
         * Wildcard variants
         * =================
         *
         *  none character | one character | two characters | token
         *  ---------------|---------------|----------------|---------------
         *               0 |             0 |              0 | (null)
         *               0 |             0 |              1 | ??
         *               0 |             1 |              0 | ?
         *               0 |             1 |              1 | **
         *               1 |             0 |              0 | (empty string)
         *               1 |             0 |              1 | ??** (special)
         *               1 |             1 |              0 | ?*
         *               1 |             1 |              1 | *
         *
         */
        public const WILDCARD_VARIANTS = [
            0 => ['', '', true],
            1 => ['s', '', false],
            2 => ['ss', '', false],

            3 => ['', '?', false],
            4 => ['s', '?', true],
            5 => ['ss', '?', false],

            6 => ['', '??', false],
            7 => ['s', '??', false],
            8 => ['ss', '??', true],
            9 => ['sss', '??', false],

            10 => ['', '*', true],
            11 => ['s', '*', true],
            12 => ['ss', '*', true],

            13 => ['', '**', false],
            14 => ['s', '**', true],
            15 => ['ss', '**', true],

            16 => ['', '?*', true],
            17 => ['s', '?*', true],
            18 => ['ss', '?*', false],

            19 => ['search phrase', 'search?phrase', true],
            20 => ['searchphrase', 'search?phrase', false],

            21 => ['search phrase', 'search?*phrase', true],
            22 => ['search  phrase', 'search?*phrase', false],
            23 => ['searchphrase', 'search?*phrase', true],
            24 => ['searchphrases', 'search?*phrase', false],

            25 => ['search phrase', 'search*phrase', true],
            26 => ['searchphrase', 'search*phrase', true],

            27 => ['search phrase', 'search**phrase', true],
            28 => ['searchphrase', 'search**phrase', false],

            29 => ['search phrase', 'search phrase?*', true],
            30 => ['search phrases', 'search phrase?*', true],
            31 => ['search phrasess', 'search phrase?*', false],

            32 => ['search phrase', '*', true],
            33 => ['search phrase', '*phrase', true],
            34 => ['search phrase', 'search*', true],

            35 => ['search phrase', '*h p*', true],
            36 => ['search phrase', '*h?p*', true],
            37 => ['search phrase', '*h*p*', true],

            38 => ['search phrase', '?????? phrase', true],
            39 => ['search phrase', 'search ??????', true],

            40 => ['search phrase', '????? phrase', false],
            41 => ['search phrase', 'search ?????', false],

            42 => ['search phrase', '*search', false],
            43 => ['search phrase', 'phrase*', false],

            44 => ['search phrase', 'false phrase', false],

            45 => ['search phrase', 'false*', false],
            46 => ['search phrase', '*false', false],

            47 => ['search phrase', 'false?', false],
            48 => ['search phrase', '?false', false],

            49 => ['search phrase', '?*s?*e?*a?*r?*c?*h?* ?*p?*h?*r?*a?*s?*e?*', true],
            50 => ['search phrase', '*s*e*a*r*c*h* *p*h*r*a*s*e*', true],

            51 => ['search phrase', '?e?r?h?p?r?s?', true],
            52 => ['search phrase', '?*e?*r?*h?*p?*r?*s?*', true],
            53 => ['search phrase', '**e**r**h**p**r**s**', true],
            54 => ['search phrase', '*e*r*h*p*r*s*', true],

            55 => ['search phrase', 's?a?c? ?h?a?e', true],
            56 => ['search phrase', 's?*a?*c?* ?*h?*a?*e', true],
            57 => ['search phrase', 's*a*c* *h*a*e', true],
            58 => ['search phrase', 's**a**c** **h**a**e', true],

            59 => ['search phrase', '?earch phra*', true],
            60 => ['search phrase', '?ea?ch*ph?a*e', true],

            61 => ['search phrase', '??arch phras*', true],
            62 => ['search phrase', '*earch phra??', true],

            63 => ['search phrase', '?ea??ch*ph?a*e', false],

            // Escaped characters
            64 => ['?', '\\?', true],
            65 => ['*', '\\*', true],

            66 => ['\\', '\\\\', true],

            67 => ['\\a', '\\\\a', true],

            68 => ['search phrase?', '*\\?', true],
            69 => ['?search phrase', '\\?*', true],
            70 => ['search\\phrase', '*\\\\*', true],

            71 => ['search\\*', '*\\*', true], // matches <search\> with wildcard, then matches escaped <*>
            72 => ['search\\*', '*\\\\*', true], // matches <search> with wildcard, then matches escaped </>, then matches <*> with wildcard
            73 => ['search\\*', '*\\\\\\*', true], // matches <search> with wildcard, then matches escaped </>, then matches escaped <*>
            74 => ['search\\*', '*\\\\\\\\*', false], // matches <search> with wildcard, then matches escaped </>, then not matches escaped </> - ignore rest of "*"

            75 => ['search\\phrase', '*\\\\phrase', true],

            76 => ['search\\phrase', 'search\\\\p*', true],
        ];

        /**
         * @var WildcardMatcher|object
         */
        protected object $subject;

        public function setUp(): void
        {
            $this->subject = $this->getObjectForTrait(WildcardMatcher::class);
        }

        public function wildcardVariantsProvider(): array
        {
            return static::WILDCARD_VARIANTS;
        }

        /**
         * @return array
         */
        public function wildcardVariantsWithInvalidCharacterProvider()
        {
            return [
                ['search phrase', '***'],
                ['search phrase', '?**'],
                ['search phrase', '?*?'],
                ['search phrase', '*?']
            ];
        }

        /**
         * @return array
         */
        public function wildcardVariantsWithInvalidEscapedCharacterProvider()
        {
            return [
                ['\\', '\\', false],
                ['a', '\\a', false],
                ['search\\phrase', '*\\phrase', false],
                ['search\\phrase', 'search\\p*', false],
            ];
        }

        /**
         * @param string $subject
         * @param string $pattern
         * @param bool $valid
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         *
         * @dataProvider wildcardVariantsProvider
         * @test
         */
        public function hasWildcardMatchReturnsResultForMatchingWildcard(string $subject, string $pattern, bool $valid)
        {
            $result = $this->subject->hasWildcardMatch($subject, $pattern);
            $this->assertEquals($valid, $result);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         *
         * @dataProvider wildcardVariantsWithInvalidCharacterProvider
         * @test
         */
        public function hasWildcardMatchThrowsInvalidCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidCharacterForWildcardPattern::class);
            $this->subject->hasWildcardMatch($subject, $pattern);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         *
         * @dataProvider wildcardVariantsWithInvalidEscapedCharacterProvider
         * @test
         */
        public function hasWildcardMatchThrowsInvalidEscapedCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidEscapedCharacterForWildcardPattern::class);
            $this->subject->hasWildcardMatch($subject, $pattern);
        }
    }
}