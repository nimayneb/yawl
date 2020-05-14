<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

    use Generator;

    /**
     *
     */
    class WildcardState
    {
        public string $subject;

        public bool $canBeZero;

        public int $maxPosition;

        public int $subjectLength;

        public bool $dynamicLength;

        public bool $found;

        protected StringFunctionsMapper $stringFunctions;

        /**
         * @param string $subject
         * @param StringFunctionsMapper $stringFunctions
         */
        public function __construct(string $subject, StringFunctionsMapper $stringFunctions = null)
        {
            $this->stringFunctions = $stringFunctions ?? new StringFunctionsMapper;
            $this->subject = $subject;
        }

        /**
         * @param string $token
         *
         * @return bool
         */
        public function analyseToken(string $token): bool
        {
            if (Token::ONE_CHARACTER === $token) {
                $this->subject = ($this->stringFunctions->substr)($this->subject, 1);
                $this->subjectLength -= 1;
                $this->maxPosition = 0;
                $this->canBeZero = true;
                $this->dynamicLength = false;
            } elseif (Token::ZERO_OR_ONE_CHARACTER === $token) {
                $this->maxPosition = 1;
                $this->canBeZero = true;
                $this->dynamicLength = true;
            } elseif (Token::ZERO_OR_MANY_CHARACTERS === $token) {
                $this->maxPosition = $this->subjectLength;
                $this->canBeZero = true;
                $this->dynamicLength = true;
            } elseif (Token::MANY_OF_CHARACTERS === $token) {
                $this->maxPosition = $this->subjectLength;
                $this->canBeZero = false;
                $this->dynamicLength = true;
            } else {
                return false;
            }

            return true;
        }

        /**
         * @param string $token
         *
         * @return bool
         */
        public function emptySubject(string $token)
        {
            if (0 === $this->subjectLength) {
                $this->found = (
                    (Token::ZERO_OR_ONE_CHARACTER === $token)
                    || (Token::ZERO_OR_MANY_CHARACTERS === $token)
                );

                return true;
            }

            return false;
        }

        /**
         * @return bool
         */
        public function isEverythingFound()
        {
            if (('' !== $this->subject) && (0 !== $this->subjectLength)) {
                $this->found = (0 !== $this->subjectLength) && ($this->subjectLength <= $this->maxPosition);
            }

            return $this->found;
        }

        /**
         * @param string $haystack
         * @param string $needle
         *
         * @return Generator
         */
        public function getPositionOfOccurrence(string $haystack, string $needle): Generator
        {
            $lastPosition = 0;

            while (false !== ($lastPosition = ($this->stringFunctions->strpos)($haystack, $needle, $lastPosition))) {
                yield $lastPosition;

                $lastPosition = $lastPosition + ($this->stringFunctions->strlen)($needle);
            }
        }

        /**
         * @param $position
         *
         * @return bool
         */
        public function ignorePosition($position)
        {
            return (
                ((false === $this->canBeZero) && (0 === $position))
                || ((true === $this->canBeZero) && (1 === $this->maxPosition) && (1 < $position))
                || ((0 === $this->maxPosition) && (0 !== $position))
            );
        }

        /**
         * @param string $token
         *
         * @return bool
         */
        public function findFixedLength(string $token): bool
        {
            if (
                (false === ($position = ($this->stringFunctions->strpos)($this->subject, $token)))
                || ((false === $this->canBeZero) && (0 === $position))
                || ((true === $this->canBeZero) && (1 === $this->maxPosition) && (1 < $position))
                || ((0 === $this->maxPosition) && (0 !== $position))
            ) {
                $this->subject = '';
                $this->subjectLength = 0;

                return false;
            }

            $start = $position + ($this->stringFunctions->strlen)($token);
            $this->subject = ($this->stringFunctions->substr)($this->subject, $start);
            $this->subjectLength -= $start;

            return true;
        }


        /**
         * @param string $subject
         * @param StringFunctionsMapper $stringFunctions
         *
         * @return WildcardState
         */
        public static function get(string $subject, StringFunctionsMapper $stringFunctions = null): WildcardState
        {
            ($stringFunctions) ?: $stringFunctions = new StringFunctionsMapper;

            $state = new static($subject, $stringFunctions);
            $state->canBeZero = true;
            $state->maxPosition = 0;
            $state->subjectLength = ($stringFunctions->strlen)($subject);
            $state->dynamicLength = false;
            $state->found = true;

            return $state;
        }
    }
}
