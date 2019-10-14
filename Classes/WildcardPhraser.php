<?php namespace JayBeeR\Wildcard {

    use Generator;

    class WildcardPhraser
    {
        use StringFunctionMapper;

        protected array $cachedResults = [];

        protected array $phrases = [];

        protected function __construct(array $phrases)
        {
            $this->phrases = array_values($phrases);
        }

        public function match(string $subject): bool
        {
            return $this->cachedResults[$subject] ?? $this->cachedResults[$subject] = $this->computePhrases($subject, 0);
        }

        protected function computePhrases(string $subject, int $i)
        {
            $found = ('' === $subject);

            if (isset($this->phrases[$i])) {
                [$phrase, $min, $max] = $this->phrases[$i];

                if (-1 === $max) {
                    $max = ($this->strlen)($subject);
                }

                if ('' !== $phrase) {
                    $newSubject = $subject;
                    $occurrence = false;

                    foreach ($this->getPositionOfOccurrence($subject, $phrase) as $position) {
                        if ('' === $newSubject) {
                            break;
                        }

                        $occurrence = true;

                        if (!(($position >= $min) && ($position <= $max))) {
                            $occurrence = false;

                            continue;
                        }

                        $newSubject = ($this->substr)($subject, ($this->strlen)($phrase) + $position);

                        if ('' === $newSubject) {
                            break;
                        }

                        $occurrence = $this->computePhrases($newSubject, $i + 1);

                        if ($occurrence) {
                            break;
                        }
                    }

                    $found = $occurrence;
                } else {
                    $length = ($this->strlen)($subject);
                    $found = (($length >= $min) && ($length <= $max));
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

        public static function get(array $phrases)
        {
            return new static($phrases);
        }
    }
} 