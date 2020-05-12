<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

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
         *
         * @return WildcardState
         */
        protected function createState(string $subject): WildcardState
        {
            $state = new WildcardState($subject);
            $state->canBeZero = true;
            $state->maxPosition = 0;
            $state->subjectLength = ($this->strlen)($subject);
            $state->dynamicLength = false;
            $state->found = true;

            return $state;
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
            $state = $this->createState($subject);

            foreach ($this->getWildcardToken($pattern) as $token => $partialPattern) {
                if ($this->emptySubject($state, $token)) {
                    break;
                }

                if (!$this->analyseToken($state, $token)) {
                    if (!$this->findPattern($state, $token, $partialPattern)) {
                        break;
                    }

                    $state->maxPosition = 0;
                    $state->canBeZero = true;
                }
            }

            return $this->isEverythingFound($state);
        }

        /**
         * @param WildcardState $state
         * @param string $token
         * @param string $partialPattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function findPattern(WildcardState $state, string $token, string $partialPattern)
        {
            $found = true;
            $this->convertEscapeToken($token);

            if ($state->dynamicLength) {
                if (!$this->findDynamicLengthWithPattern($state, $token, $partialPattern)) {
                    $found = false;
                } else {
                    $state->found = false;
                }
            } elseif (!$this->findFixedLength($state, $token)) {
                $state->found = false;
                $found = false;
            }

            return $found;
        }

        /**
         * @param WildcardState $state
         * @param string $token
         *
         * @return bool
         */
        protected function emptySubject(WildcardState $state, string $token)
        {
            if (0 === $state->subjectLength) {
                $state->found = (
                    (Token::ZERO_OR_ONE_CHARACTER === $token)
                    || (Token::ZERO_OR_MANY_CHARACTERS === $token)
                );

                return true;
            }

            return false;
        }

        /**
         * @param WildcardState $state
         *
         * @return bool
         */
        protected function isEverythingFound(WildcardState $state)
        {
            if (('' !== $state->subject) && (0 !== $state->subjectLength)) {
                $state->found = (0 !== $state->subjectLength) && ($state->subjectLength <= $state->maxPosition);
            }

            return $state->found;
        }

        /**
         * @param WildcardState $state
         * @param string $token
         * @param string $partialPattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function findDynamicLengthWithPattern(WildcardState $state, string $token, string $partialPattern): bool
        {
            $occurrences = 0;

            foreach ($this->getPositionOfOccurrence($state->subject, $token) as $position) {
                $occurrences++;

                if ($this->ignorePosition($state, $position)) {
                    continue;
                }

                $start = $position + ($this->strlen)($token);
                $newSubject = ($this->substr)($state->subject, $start);

                if ('' !== $partialPattern) {
                    if ($this->hasWildcardMatch($newSubject, $partialPattern)) {
                        $state->subject = '';
                        $state->subjectLength = 0;

                        return false;
                    }
                } elseif ('' === $newSubject) {
                    $state->subject = '';
                    $state->subjectLength = 0;

                    return false;
                }
            }

            $state->subject = '';
            $state->subjectLength = 0;

            return true;
        }

        /**
         * @param WildcardState $state
         * @param $position
         *
         * @return bool
         */
        protected function ignorePosition(WildcardState $state, $position)
        {
            return (
                ((false === $state->canBeZero) && (0 === $position))
                || ((true === $state->canBeZero) && (1 === $state->maxPosition) && (1 < $position))
                || ((0 === $state->maxPosition) && (0 !== $position))
            );
        }

        /**
         * @param WildcardState $state
         * @param string $token
         *
         * @return bool
         */
        protected function findFixedLength(WildcardState $state, string $token): bool
        {
            if (
                (false === ($position = ($this->strpos)($state->subject, $token)))
                || ((false === $state->canBeZero) && (0 === $position))
                || ((true === $state->canBeZero) && (1 === $state->maxPosition) && (1 < $position))
                || ((0 === $state->maxPosition) && (0 !== $position))
            ) {
                $state->subject = '';
                $state->subjectLength = 0;

                return false;
            }

            $start = $position + ($this->strlen)($token);
            $state->subject = ($this->substr)($state->subject, $start);
            $state->subjectLength -= $start;

            return true;
        }

        /**
         * @param string $token
         */
        protected function convertEscapeToken(string &$token): void
        {
            if (chr(0) === $token[0]) {
                $token = $token[1];
            }
        }

        /**
         * @param WildcardState $state
         * @param string $token
         *
         * @return bool
         */
        protected function analyseToken(WildcardState $state, string $token): bool
        {
            if (Token::ONE_CHARACTER === $token) {
                $state->subject = ($this->substr)($state->subject, 1);
                $state->subjectLength -= 1;
                $state->maxPosition = 0;
                $state->canBeZero = true;
                $state->dynamicLength = false;
            } elseif (Token::ZERO_OR_ONE_CHARACTER === $token) {
                $state->maxPosition = 1;
                $state->canBeZero = true;
                $state->dynamicLength = true;
            } elseif (Token::ZERO_OR_MANY_CHARACTERS === $token) {
                $state->maxPosition = $state->subjectLength;
                $state->canBeZero = true;
                $state->dynamicLength = true;
            } elseif (Token::MANY_OF_CHARACTERS === $token) {
                $state->maxPosition = $state->subjectLength;
                $state->canBeZero = false;
                $state->dynamicLength = true;
            } else {
                return false;
            }

            return true;
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

                $this->assertValidCharacters($token, $previousToken, $pattern, $position);
                $this->setSpecialToken($token, $pattern, $nextToken);

                $previousToken = $token;

                if ($escapeToken = $this->hasEscapeToken($token, $pattern, $position)) {
                    yield $escapeToken[0] => $escapeToken[1];

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
         * @param string $token
         * @param string $pattern
         * @param string $nextToken
         */
        protected function setSpecialToken(string &$token, string &$pattern, ?string $nextToken): void
        {
            // 1. combine two tokens (**) 1-x
            // 2. combine two tokens (?*) 0-1

            if ((Token::ZERO_OR_MANY_CHARACTERS === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
                $token = Token::MANY_OF_CHARACTERS;
                $pattern = ($this->substr)($pattern, 1);
            } elseif ((Token::ONE_CHARACTER === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
                $token = Token::ZERO_OR_ONE_CHARACTER;
                $pattern = ($this->substr)($pattern, 1);
            }
        }

        /**
         * @param string $token
         * @param string $pattern
         * @param int $position
         *
         * @return array|null
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function hasEscapeToken(string $token, string &$pattern, int $position): ?array
        {
            //  escaped characters: \? \*
            // backslash character: \

            if (Token::ESCAPE_CHAR === $token) {
                if ((isset($pattern[0])) && ($this->hasNextToken($escapeChar = $pattern[0]))) {
                    $pattern = ($this->substr)($pattern, 1);
                } else {
                    throw new InvalidEscapedCharacterForWildcardPattern($pattern, $position);
                }

                $escapeChar = chr(0) . $escapeChar;

                return [$escapeChar, $pattern];
            }

            return null;
        }

        /**
         * @param string $token
         * @param string $previousToken
         * @param string $pattern
         * @param int $position
         *
         * @throws InvalidCharacterForWildcardPattern
         */
        protected function assertValidCharacters(string $token, ?string $previousToken, string $pattern, int $position): void
        {
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
        }

        /**
         * @param string $character
         *
         * @return bool
         */
        protected function hasNextToken(string $character): bool
        {
            return (
                (Token::ZERO_OR_MANY_CHARACTERS === $character)
                || (Token::ONE_CHARACTER === $character)
                || (Token::ESCAPE_CHAR === $character)
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
                    ($this->strpos)($pattern, Token::ESCAPE_CHAR),
                ],
                fn($value) => false !== $value
            );

            return (!empty($positions) ? min($positions) : null);
        }
    }
}
