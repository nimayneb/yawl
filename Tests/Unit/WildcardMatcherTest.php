<?php  declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard\Tests\Unit {

    use Exception;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Failures\InvalidEscapedCharacterForWildcardPattern;
    use JayBeeR\Wildcard\StringFunctionsMapper;
    use JayBeeR\Wildcard\Tests\Helper\WildcardGenerator;
    use JayBeeR\Wildcard\WildcardMatcher;

    /**
     *
     */
    class WildcardMatcherTest extends WildcardTest
    {
        /**
         * @var WildcardMatcher|object
         */
        protected object $subject;

        protected StringFunctionsMapper $stringFunctions;

        /**
         *
         */
        public function setUp(): void
        {
            $this->stringFunctions = new StringFunctionsMapper;
            $this->subject = new WildcardMatcher($this->stringFunctions);
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
            $this->stringFunctions->setSingleByte();

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
            $this->stringFunctions->setMultiByte();

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
            $this->stringFunctions->setSingleByte();
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
            $this->stringFunctions->setMultiByte();
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
            $this->stringFunctions->setSingleByte();
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
            $this->stringFunctions->setMultiByte();
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
            $this->stringFunctions->setSingleByte();
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
            $this->stringFunctions->setMultiByte();
            $this->subject->match($subject, $pattern);
        }
    }
}
