<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

    use Generator;

    class WildcardPerformer
    {
        use StringFunctionMapper;

        protected array $cachedResults = [];

        protected array $phrases = [];

        /**
         * @param array $phrases
         */
        protected function __construct(array $phrases)
        {
            $this->phrases = array_values($phrases);
        }

        /**
         * @param string $subject
         *
         * @return bool
         */
        public function match(string $subject): bool
        {
            return $this->cachedResults[$subject]
                ?? $this->cachedResults[$subject] = $this->computePhrases($subject, 0);
        }

        /**
         * @param string $subject
         * @param int $index
         *
         * @return bool|mixed
         */
        protected function computePhrases(string $subject, int $index)
        {
            $found = ('' === $subject);

            if (isset($this->phrases[$index])) {
                [$phrase, $min, $max] = $this->phrases[$index];

                if (-1 === $max) {
                    $max = ($this->strlen)($subject);
                }

                if ('' !== $phrase) {
                    $found = $this->findOccurrence($subject, $phrase, $min, $max, $index);
                } else {
                    $length = ($this->strlen)($subject);
                    $found = (($length >= $min) && ($length <= $max));
                }
            }

            return $found;
        }

        /**
         * @param string $subject
         * @param string $phrase
         * @param int $min
         * @param int $max
         * @param int $index
         *
         * @return bool
         */
        protected function findOccurrence(string $subject, string $phrase, int $min, int $max, int $index): bool
        {
            $found = false;
            $newSubject = $subject;

            foreach ($this->getPositionOfOccurrence($subject, $phrase) as $position) {
                if ('' === $newSubject) {
                    break;
                }

                $found = true;

                if (!(($position >= $min) && ($position <= $max))) {
                    $found = false;

                    continue;
                }

                $newSubject = ($this->substr)($subject, ($this->strlen)($phrase) + $position);

                if ('' === $newSubject) {
                    break;
                }

                $found = $this->computePhrases($newSubject, $index + 1);

                if ($found) {
                    break;
                }
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
         * @param array $phrases
         *
         * @return static
         */
        public static function get(array $phrases)
        {
            return new static($phrases);
        }
    }
} 