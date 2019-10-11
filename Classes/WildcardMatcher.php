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

        protected array $cachedResults = [];

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        public function matchWildcard(string $subject, string $pattern): bool
        {
            return $this->cachedResults[$pattern][$subject]
                ?? ($this->cachedResults[$pattern][$subject] = $this->hasWildcardMatch($subject, $pattern));
        }

        /**
         * @return int
         */
        public function getCachedSize(): int
        {
            return count($this->cachedResults);
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function hasWildcardMatch(string $subject, string $pattern): bool
        {
            $found = true;
            $canBeZero = true;
            $maxPosition = 0;
            $subjectLength = ($this->strlen)($subject);
            $dynamicLength = false;

            foreach ($this->getWildcardToken($pattern) as $token => $partialPattern) {
                if (0 === $subjectLength) {
                    $found = (
                        (Token::ZERO_OR_ONE_CHARACTER === $token)
                        || (Token::ZERO_OR_MANY_CHARACTERS === $token)
                    );

                    break;
                }

                if (Token::ONE_CHARACTER === $token) {
                    $subject = ($this->substr)($subject, 1);
                    $subjectLength -= 1;
                    $maxPosition = 0;
                    $canBeZero = true;
                    $dynamicLength = false;
                } elseif (Token::ZERO_OR_ONE_CHARACTER === $token) {
                    $maxPosition = 1;
                    $canBeZero = true;
                    $dynamicLength = true;
                } elseif (Token::ZERO_OR_MANY_CHARACTERS === $token) {
                    $maxPosition = $subjectLength;
                    $canBeZero = true;
                    $dynamicLength = true;
                } elseif (Token::MANY_OF_CHARACTERS === $token) {
                    $maxPosition = $subjectLength;
                    $canBeZero = false;
                    $dynamicLength = true;
                } else {
                    if (chr(0) === $token[0]) {
                        $token = $token[1];
                    }

                    if ($dynamicLength) {
                        $occurrences = 0;

                        foreach ($this->getPositionOfOccurrence($subject, $token) as $position) {
                            $occurrences++;

                            if (
                                ((false === $canBeZero) && (0 === $position))
                                || ((true === $canBeZero) && (1 === $maxPosition) && (1 < $position))
                                || ((0 === $maxPosition) && (0 !== $position))
                            ) {
                                continue;
                            }

                            $start = $position + ($this->strlen)($token);
                            $newSubject = ($this->substr)($subject, $start);

                            if ('' !== $partialPattern) {
                                $result = $this->hasWildcardMatch($newSubject, $partialPattern);

                                if ($result) {
                                    $subject = '';
                                    $subjectLength = 0;

                                    break 2;
                                }
                            } elseif ('' === $newSubject) {
                                $subject = '';
                                $subjectLength = 0;

                                break 2;
                            }
                        }

                        if (0 === $occurrences) {
                            $subject = '';
                            $subjectLength = 0;
                            $found = false;

                            break;
                        }
                    } else {
                        if (
                            (false === ($position = ($this->strpos)($subject, $token)))
                            || ((false === $canBeZero) && (0 === $position))
                            || ((true === $canBeZero) && (1 === $maxPosition) && (1 < $position))
                            || ((0 === $maxPosition) && (0 !== $position))
                        ) {
                            $subject = '';
                            $subjectLength = 0;
                            $found = false;

                            break;
                        }

                        $start = $position + ($this->strlen)($token);
                        $subject = ($this->substr)($subject, $start);
                        $subjectLength -= $start;
                    }

                    $maxPosition = 0;
                    $canBeZero = true;
                }
            }

            if (('' !== $subject) && (0 !== $subjectLength)) {
                $found = ($subjectLength <= $maxPosition);
            }

            return $found;
        }

        /**
         * @param string $haystack
         * @param string $needle
         *
         * @return Generator
         */
        protected function getPositionOfOccurrence(string $haystack, string $needle): Generator
        {
            $lastPosition = 0;

            while (false !== ($lastPosition = ($this->strpos)($haystack, $needle, $lastPosition))) {
                yield $lastPosition;

                $lastPosition = $lastPosition + ($this->strlen)($needle);
            }
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
                $token = $pattern[$position];
                $nextToken = (isset($pattern[$position + 1]) ? $pattern[$position + 1] : null);

                // search phrase

                if (0 < $position) {
                    $previousToken = null;

                    yield ($this->substr)($pattern, 0, $position) => ($this->substr)($pattern, $position);
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
                    $token = Token::MANY_OF_CHARACTERS;
                    $pattern = ($this->substr)($pattern, 1);
                } elseif ((Token::ONE_CHARACTER === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
                    $token = Token::ZERO_OR_ONE_CHARACTER;
                    $pattern = ($this->substr)($pattern, 1);
                }

                $previousToken = $token;

                //  escaped characters: \? \*
                // backslash character: \

                if (Token::ESCAPE_CHAR === $token) {
                    if ((isset($pattern[0])) && ($this->hasNextToken($escapeChar = $pattern[0]))) {
                        $pattern = ($this->substr)($pattern, 1);
                        yield chr(0) . $escapeChar => $pattern;
                    } else {
                        throw new InvalidEscapedCharacterForWildcardPattern($pattern, $position);
                    }

                    continue;
                }

                yield $token => $pattern;
            }

            // search phrase

            if (0 < ($this->strlen)($pattern)) {
                yield $pattern => '';
            }
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
