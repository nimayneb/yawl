<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.yawl".
     * See LICENSE.txt that was shipped with this package.
     */

    use Generator;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Failures\InvalidEscapedCharacterForWildcardPattern;

    trait WildcardMatcher
    {
        use StringFunctionMapper;

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        public function hasWildcardMatch(string $subject, string $pattern): bool
        {
            $found = true;
            $canBeZero = true;
            $neededLength = 0;
            $maxLength = ($this->strlen)($subject);
echo "\n\n<$subject>($pattern)\n";
            foreach ($this->getWildcardToken($pattern) as $token) {
echo "'$token'($subject:$maxLength)\n";
                if (0 === $maxLength) {
echo "0";
                    $found = (
                        (Token::ZERO_OR_ONE_CHARACTER === $token)
                        || (Token::ZERO_OR_MANY_CHARACTERS === $token)
                    );

                    break;
                }

                if (Token::ONE_CHARACTER === $token) {
echo "1";
echo "($subject)";
                    $subject = ($this->substr)($subject, 1);
                    $maxLength -= 1;

                    $neededLength = 0;
                    $canBeZero = true;
                } elseif (Token::ZERO_OR_ONE_CHARACTER === $token) {
echo "2";
                    $neededLength = 1;
                    $canBeZero = true;
                } elseif (Token::ZERO_OR_MANY_CHARACTERS === $token) {
echo "3";
                    $neededLength = $maxLength;
                    $canBeZero = true;
                } elseif (Token::MANY_OF_CHARACTERS === $token) {
echo "4";
                    $neededLength = $maxLength;
                    $canBeZero = false;
                } else {
echo "5";
                    if (($this->chr)(0) === $token[0]) {
echo "6";
                        $token = $token[1];
                    }

                    if (
                        (false === ($position = ($this->strpos)($subject, $token)))
                        || ((false === $canBeZero) && (0 === $position))
                        || ((true === $canBeZero) && (1 === $neededLength) && (1 < $position))
                        || ((0 === $neededLength) && (0 !== $position))
                    ) {
echo "7";
echo "($subject)";
                        $subject = '';
                        $maxLength = 0;
                        $found = false;

                        break;
                    }

                    $start = $position + ($this->strlen)($token);
echo "($subject#$start)";
                    $subject = ($this->substr)($subject, $start);
                    $maxLength -= $start;

                    $neededLength = 0;
                    $canBeZero = true;
                }
echo "($subject:$maxLength)";
            }

            if (('' !== $subject) && (0 !== $maxLength)) {
echo "8";
                $found = ($maxLength <= $neededLength);
            }
echo "\n" . ($found?'true':'false') . "\n\n";
            return $found;
        }

        /**
         * @param string $pattern
         *
         * @return Generator|string[]
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function getWildcardToken(string $pattern): Generator
        {
            $previousToken = null;

            while (null !== ($position = $this->findNextToken($pattern))) {
echo "#{$position}#";
                $token = $pattern[$position];
                $nextToken = (isset($pattern[$position + 1]) ? $pattern[$position + 1] : null);

                // search phrase

                if (0 < $position) {
echo "A";
                    $previousToken = null;

                    yield ($this->substr)($pattern, 0, $position);
                }

                $pattern = ($this->substr)($pattern, $position + 1);

                // 1. no combination of token (***)
                // 2. no combination of token (?**)
                // 3. no combination of token (?*?)
                // 4. no combination of token (*?)

                if (
                    ((Token::MANY_OF_CHARACTERS === $previousToken) && (Token::ZERO_OR_MANY_CHARACTERS === $token))
                    || ((Token::ZERO_OR_MANY_CHARACTERS === $previousToken) && (Token::ONE_CHARACTER === $token))
                    || ((Token::ZERO_OR_ONE_CHARACTER === $previousToken) && (Token::ONE_CHARACTER === $token))
                    || ((Token::ZERO_OR_ONE_CHARACTER === $previousToken) && (Token::ZERO_OR_MANY_CHARACTERS === $token))
                ) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $position);
                }

                // 1. combine two tokens (**) 1-x
                // 2. combine two tokens (?*) 0-1

                if ((Token::ZERO_OR_MANY_CHARACTERS === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
echo "B";
                    $token = Token::MANY_OF_CHARACTERS;
                    $pattern = ($this->substr)($pattern, 1);
                } elseif ((Token::ONE_CHARACTER === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
echo "C";
                    $token = Token::ZERO_OR_ONE_CHARACTER;
                    $pattern = ($this->substr)($pattern, 1);
                }

                $previousToken = $token;

                //  escaped characters: \? \*
                // backslash character: \

                if (Token::ESCAPE_CHAR === $token) {
echo "D";
                    if ((!isset($pattern[0])) || (!$this->hasNextToken($escapeChar = $pattern[0]))) {
                        throw new InvalidEscapedCharacterForWildcardPattern($pattern, $position);
                    } else {
echo "E";
                        yield ($this->chr)(0) . $escapeChar;

                        $pattern = ($this->substr)($pattern, 1);
                    }

                    continue;
                }

                yield $token;
echo "F";
            }

            // search phrase

            if (0 < ($this->strlen)($pattern)) {
echo "G";
                yield $pattern;
            }
echo "H";
        }

        /**
         * @param string $character
         *
         * @return bool
         */
        protected function hasNextToken(string $character): bool
        {
            return (
                (Token::ZERO_OR_MANY_CHARACTERS === $character[0])
                || (Token::ONE_CHARACTER === $character[0])
                || (Token::ESCAPE_CHAR === $character[0])
            );
        }

        /**
         * @param string $pattern
         *
         * @return int|null
         */
        protected function findNextToken(string $pattern): ?int
        {
            $positions = array_filter(
                [
                    ($this->strpos)($pattern, Token::ZERO_OR_MANY_CHARACTERS),
                    ($this->strpos)($pattern, Token::ONE_CHARACTER),
                    ($this->strpos)($pattern, Token::ESCAPE_CHAR)
                ],
                fn ($value) => false !== $value
            );

            return (!empty($positions) ? min($positions) : null);
        }
    }
}
