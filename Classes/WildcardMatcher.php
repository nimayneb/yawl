<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

    use Generator;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Failures\InvalidEscapedCharacterForWildcardPattern;

    /**
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Reason: because of Factory calls
     */
    class WildcardMatcher
    {
        protected array $cachedResults = [];

        protected StringFunctionsMapper $stringFunctions;

        /**
         * @param StringFunctionsMapper $stringFunctions
         */
        public function __construct(StringFunctionsMapper $stringFunctions = null)
        {
            $this->stringFunctions = $stringFunctions ?? new StringFunctionsMapper;
        }

        /**
         * @param string $subject
         * @param string $pattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        public function match(string $subject, string $pattern): bool
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
            $state = WildcardState::get($subject, $this->stringFunctions);

            foreach ($this->getWildcardToken($pattern) as $token => $partialPattern) {
                if ($state->emptySubject($token)) {
                    break;
                }

                if (!$state->analyseToken($token)) {
                    if (!$this->findPattern($state, $token, $partialPattern)) {
                        break;
                    }

                    $state->maxPosition = 0;
                    $state->canBeZero = true;
                }
            }

            return $state->isEverythingFound();
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
        public function findPattern(WildcardState $state, string $token, string $partialPattern)
        {
            $found = true;
            $this->convertEscapeToken($token);

            if ($state->dynamicLength) {
                if (!$this->findDynamicLengthWithPattern($state, $token, $partialPattern)) {
                    $found = false;
                } else {
                    $state->found = false;
                }
            } elseif (!$state->findFixedLength($token)) {
                $state->found = false;
                $found = false;
            }

            return $found;
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
         * @param string $partialPattern
         *
         * @return bool
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function findDynamicLengthWithPattern(WildcardState $state, string $token, string $partialPattern): bool
        {
            $occurrences = 0;

            foreach ($state->getPositionOfOccurrence($state->subject, $token) as $position) {
                $occurrences++;

                if ($state->ignorePosition($position)) {
                    continue;
                }

                $start = $position + ($this->stringFunctions->strlen)($token);
                $newSubject = ($this->stringFunctions->substr)($state->subject, $start);

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

                    yield ($this->stringFunctions->substr)($pattern, 0, $position) => ($this->stringFunctions->substr)($pattern, $position);
                }

                $pattern = ($this->stringFunctions->substr)($pattern, $position + 1);

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

            if (0 < ($this->stringFunctions->strlen)($pattern)) {
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
                $pattern = ($this->stringFunctions->substr)($pattern, 1);
            } elseif ((Token::ONE_CHARACTER === $token) && (Token::ZERO_OR_MANY_CHARACTERS === $nextToken)) {
                $token = Token::ZERO_OR_ONE_CHARACTER;
                $pattern = ($this->stringFunctions->substr)($pattern, 1);
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
                    $pattern = ($this->stringFunctions->substr)($pattern, 1);
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
                    ($this->stringFunctions->strpos)($pattern, Token::ZERO_OR_MANY_CHARACTERS),
                    ($this->stringFunctions->strpos)($pattern, Token::ONE_CHARACTER),
                    ($this->stringFunctions->strpos)($pattern, Token::ESCAPE_CHAR),
                ],
                fn($value) => false !== $value
            );

            return (!empty($positions) ? min($positions) : null);
        }
    }
}
