<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.wildcard-trait".
     * See LICENSE.txt that was shipped with this package.
     */

    use Generator;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;

    trait WildcardMatcher
    {
        /**
         * @param string $subject
         * @param string $pattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         */
        public function hasWildcardMatch(string $subject, string $pattern): bool
        {
            $found = true;
            $canBeNull = true;
            $neededLength = 0;

            foreach ($this->getWildcardToken($pattern) as $token) {
                if (0 === strlen($subject)) {
                    $found = (
                        (Token::ZERO_OR_ONE_CHARACTER === $token)
                        || (Token::ZERO_OR_MANY_CHARACTERS === $token)
                    );

                    break;
                }

                if (Token::ONE_CHARACTER === $token) {
                    $subject = substr($subject, 1);
                    $neededLength = 0;
                    $canBeNull = true;
                } elseif (Token::ZERO_OR_ONE_CHARACTER === $token) {
                    $neededLength = 1;
                    $canBeNull = true;
                } elseif (Token::ZERO_OR_MANY_CHARACTERS === $token) {
                    $neededLength = PHP_INT_MAX;
                    $canBeNull = true;
                } elseif (Token::MANY_OF_CHARACTERS === $token) {
                    $neededLength = PHP_INT_MAX;
                    $canBeNull = false;
                } else {
                    if (chr(0) === $token[0]) {
                        $token = $token[1];
                    }

                    if (
                        (false === ($position = strpos($subject, $token)))
                        || ((false === $canBeNull) && (0 === $position))
                        || ((0 === $neededLength) && (0 !== $position))
                    ) {
                        $subject = '';
                        $found = false;

                        break;
                    }

                    $subject = substr($subject, $position + strlen($token));
                    $neededLength = 0;
                    $canBeNull = true;
                }
            }

            if (0 !== ($length = strlen($subject))) {
                $found = ($length <= $neededLength);
            }

            return $found;
        }

        /**
         * @param string $pattern
         *
         * @return Generator|string[]
         * @throws InvalidCharacterForWildcardPattern
         */
        protected function getWildcardToken(string $pattern): Generator
        {
            $previousToken = null;

            while (null !== ($position = $this->findNextToken($pattern))) {
                $token = $pattern[$position];
                $nextToken = (isset($pattern[$position + 1]) ? $pattern[$position + 1] : null);

                // search phrase

                if (0 < $position) {
                    $previousToken = null;

                    yield substr($pattern, 0, $position);
                }

                $pattern = substr($pattern, $position + 1);

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
                    $token = Token::MANY_OF_CHARACTERS;
                    $pattern = substr($pattern, 1);
                } elseif ((Token::ONE_CHARACTER === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
                    $token = Token::ZERO_OR_ONE_CHARACTER;
                    $pattern = substr($pattern, 1);
                }

                $previousToken = $token;

                //  escaped characters: \? \*
                // backslash character: \

                if (Token::ESCAPE_CHAR === $token) {
                    $escapeChar = $pattern[0];

                    if (null === $this->findNextToken($escapeChar)) {
                        yield chr(0) . $token;
                    } else {
                        yield chr(0) . $escapeChar;

                        $pattern = substr($pattern, 1);
                    }

                    continue;
                }

                yield $token;
            }

            // search phrase

            if (0 < strlen($pattern)) {
                yield $pattern;
            }
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
                    strpos($pattern, Token::ZERO_OR_MANY_CHARACTERS),
                    strpos($pattern, Token::ONE_CHARACTER),
                    strpos($pattern, Token::ESCAPE_CHAR)
                ],
                fn ($value) => false !== $value
            );

            return (!empty($positions) ? min($positions) : null);
        }
    }
}
