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
    use JayBeeR\Wildcard\WildcardFactory;
    use JayBeeR\Wildcard\WildcardPerformer;

    class WildcardPerformerTest extends WildcardTest
    {
        /**
         * @var WildcardPerformer|object
         */
        protected object $subject;

        public function setUp(): void
        {
            $this->subject = new WildcardFactory();
        }

        /**
         * @throws InvalidCharacterForWildcardPattern
         * @throws Exception
         * @test
         */
        public function matchWildcardForSingleByteWithRandomStringsAndCorrespondingPatternsReturnsTrue()
        {
            foreach (WildcardGenerator::getRandomCount(2000) as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                $this->subject->setSingleByte();
                $performer = $this->subject->get($wildcard);
                $result = $performer->match($subject);
                $this->assertTrue($result, sprintf('%d: <%s> corresponding <%s>', $index, $subject, $wildcard));
            }
        }

        /**
         * @throws InvalidCharacterForWildcardPattern
         * @throws Exception
         * @test
         */
        public function matchWildcardForMultiByteWithRandomStringsAndCorrespondingPatternsReturnsTrue()
        {
            foreach (WildcardGenerator::getRandomCount(2000) as $index => ['subject' => $subject, 'wildcard' => $wildcard]) {
                $this->subject->setMultiByte();
                $performer = $this->subject->get($wildcard);
                $result = $performer->match($subject);
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
         * @dataProvider wildcardVariantsProvider
         * @test
         */
        public function hasSingleByteWildcardMatchReturnsResultForMatchingWildcard(string $subject, string $pattern, bool $valid)
        {
            $this->subject->setSingleByte();
            $performer = $this->subject->get($pattern);
            $result = $performer->match($subject);

            $this->assertEquals($valid, $result);
        }

        /**
         * @param string $subject
         * @param string $pattern
         * @param bool $valid
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @dataProvider wildcardVariantsProvider
         * @test
         */
        public function hasMultiByteWildcardMatchReturnsResultForMatchingWildcard(string $subject, string $pattern, bool $valid)
        {
            $this->subject->setMultiByte();
            $performer = $this->subject->get($pattern);
            $result = $performer->match($subject);

            $this->assertEquals($valid, $result);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @dataProvider wildcardVariantsWithInvalidCharacterProvider
         * @test
         */
        public function hasSingleByteWildcardMatchThrowsInvalidCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidCharacterForWildcardPattern::class);

            $this->subject->setSingleByte();
            $performer = $this->subject->get($pattern);
            $performer->match($subject);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @dataProvider wildcardVariantsWithInvalidCharacterProvider
         * @test
         */
        public function hasMultiByteWildcardMatchThrowsInvalidCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidCharacterForWildcardPattern::class);

            $this->subject->setMultiByte();
            $performer = $this->subject->get($pattern);
            $performer->match($subject);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @dataProvider wildcardVariantsWithInvalidEscapedCharacterProvider
         * @test
         */
        public function hasSingleByteWildcardMatchThrowsInvalidEscapedCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidEscapedCharacterForWildcardPattern::class);

            $this->subject->setSingleByte();
            $performer = $this->subject->get($pattern);
            $performer->match($subject);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         * @dataProvider wildcardVariantsWithInvalidEscapedCharacterProvider
         * @test
         */
        public function hasMultiByteWildcardMatchThrowsInvalidEscapedCharacterException(string $subject, string $pattern)
        {
            $this->expectException(InvalidEscapedCharacterForWildcardPattern::class);

            $this->subject->setMultiByte();
            $performer = $this->subject->get($pattern);
            $performer->match($subject);
        }
    }
}