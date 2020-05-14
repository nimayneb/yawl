<?php  declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard\Tests\Unit {

    use Exception;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Failures\InvalidEscapedCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Tests\Helper\WildcardGenerator;
    use JayBeeR\Wildcard\WildcardMatcher;

    class WildcardMatcherTest extends WildcardTest
    {
        /**
         * @var WildcardMatcher
         */
        protected object $subject;

        public function setUp(): void
        {
            $this->subject = new WildcardMatcher;
        }

        /**
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @throws Exception
         *
         * @test
         */
        public function matchWildcardForSingleByteWithRandomStringsAndCorrespondingPatternsReturnsTrue()
        {
            $this->subject->setSingleByte();

            foreach (WildcardGenerator::getRandomCount(2000) as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                $result = $this->subject->match($subject, $wildcard);
                $this->assertTrue($result, sprintf('%d: <%s> corresponding <%s>', $index, $subject, $wildcard));
            }
        }

        /**
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @throws Exception
         *
         * @test
         */
        public function matchWildcardForMultiByteWithRandomStringsAndCorrespondingPatternsReturnsTrue()
        {
            $this->subject->setMultiByte();

            foreach (WildcardGenerator::getRandomCount(2000) as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                $result = $this->subject->match($subject, $wildcard);
                $this->assertTrue($result, sprintf('%d: <%s> corresponding <%s>', $index, $subject, $wildcard));
            }
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
        public function hasSingleByteWildcardMatchReturnsResultForMatchingWildcard(string $subject, string $pattern, bool $valid)
        {
            $this->subject->setSingleByte();
            $result = $this->subject->match($subject, $pattern);
            $this->assertEquals($valid, $result);
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
        public function hasMultiByteWildcardMatchReturnsResultForMatchingWildcard(string $subject, string $pattern, bool $valid)
        {
            $this->subject->setMultiByte();
            $result = $this->subject->match($subject, $pattern);
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
        public function hasSingleByteWildcardMatchThrowsInvalidCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidCharacterForWildcardPattern::class);
            $this->subject->setSingleByte();
            $this->subject->match($subject, $pattern);
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
        public function hasMultiByteWildcardMatchThrowsInvalidCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidCharacterForWildcardPattern::class);
            $this->subject->setMultiByte();
            $this->subject->match($subject, $pattern);
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
        public function hasSingleByteWildcardMatchThrowsInvalidEscapedCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidEscapedCharacterForWildcardPattern::class);
            $this->subject->setSingleByte();
            $this->subject->match($subject, $pattern);
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
        public function hasMultiByteWildcardMatchThrowsInvalidEscapedCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidEscapedCharacterForWildcardPattern::class);
            $this->subject->setMultiByte();
            $this->subject->match($subject, $pattern);
        }
    }
}

// xUliIj6HGHBcxPefaYV1hvGVK7490jJGNm8xzoaKu0vA7ZSNcEQBtA3giWSHXO1mtHdrhteRcFMKJG9PedXf4cukidbzEUghROwCf0oCn8Txk51ZfsX30VyyzKnUZE8tvNba9uLBmpPTCksUSWlFE6J6YBrMygnqdw9YROjwDDDzpQLnMyCRJMLvIk2ALsNQh
// xUliIj6HGHBcxPefaYV1hvGVK7490jJGNm8xzoaKu0vA7ZSNcEQBtA3giWSHXO1mt**                                                   yyzKnUZE8tv**                                 RO???DDzpQLnMyCRJ????k2AL*N??