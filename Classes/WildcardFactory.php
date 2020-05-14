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
    class WildcardFactory
    {
        protected bool $skipToken;

        protected ?string $previousToken;

        protected array $phrases;

        protected int $index;

        protected string $phrase;

        protected StringFunctionsMapper $stringFunctions;

        /**
         * @var WildcardPerformer[]
         */
        protected static array $cachedPattern = [];

        /**
         * @param StringFunctionsMapper $stringFunctions
         */
        public function __construct(StringFunctionsMapper $stringFunctions = null)
        {
            $this->stringFunctions = $stringFunctions ?? new StringFunctionsMapper;
            $this->stringFunctions->setSingleByte();
        }

        /**
         * @param string $pattern
         *
         * @return WildcardPerformer
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function create(string $pattern): WildcardPerformer
        {
            $this->reset();

            foreach ($this->nextCharacter($pattern) as $offset => $token) {
                if ($this->skipToken) {
                    $this->skipToken = false;

                    continue;
                }

                $nextToken = ($this->stringFunctions->substr)($pattern, $offset + 1, 1);

                if (!$this->analyseToken($token, $nextToken)) {
                    $this->analyseInvalidPreviousToken($token, $nextToken, $pattern, $offset);
                }

                switch ($token) {
                    case '?':
                    {
                        $this->addPhraseForQuery();

                        break;
                    }

                    case '*':
                    {
                        $this->addPhraseForAsterisk();

                        break;
                    }

                    case '**':
                    {
                        $this->addPhraseForDoubleAsterisks();

                        break;
                    }

                    case '?*':
                    {
                        $this->addPhraseForQueryAsterisk();

                        break;
                    }

                    default:
                    {
                        $this->addPhraseForWord($token);
                    }
                }

                $this->previousToken = $token;
            }

            $phraser = WildcardPerformer::get($this->phrases);
            $phraser->setStringFunctions($this->stringFunctions);

            return $phraser;
        }

        /**
         *
         */
        protected function reset(): void
        {
            $this->skipToken = false;
            $this->previousToken = null;
            $this->phrases = [];
            $this->phrase = '';
            $this->index = 0;
        }

        /**
         * @param string $token
         */
        protected function addPhraseForWord(string $token): void
        {
            // Decode escaped character
            if (chr(0) === $token[0]) {
                $token = $token[1];
            }

            $this->phrase .= $token;

            if (!isset($this->phrases[$this->index])) {
                $this->phrases[$this->index] = [$this->phrase, 0, 0];
            } else {
                $this->phrases[$this->index][0] = $this->phrase;
            }
        }

        /**
         *
         */
        protected function addPhraseForQueryAsterisk(): void
        {
            if (isset($this->phrases[$this->index][0])) {
                $this->phrase = '';
            }

            if ('?' === $this->previousToken) {
                $this->phrases[$this->index][2]++;
            } else {
                $this->phrases[++$this->index] = [$this->phrase, 0, 1];
                $this->phrase = '';
            }
        }

        /**
         *
         */
        protected function addPhraseForDoubleAsterisks(): void
        {
            if (isset($this->phrases[$this->index][0])) {
                $this->phrase = '';
            }

            $this->phrases[++$this->index] = [$this->phrase, 1, -1];
            $this->phrase = '';
        }

        /**
         *
         */
        protected function addPhraseForAsterisk(): void
        {
            if (isset($this->phrases[$this->index][0])) {
                $this->phrase = '';
            }

            $this->phrases[++$this->index] = [$this->phrase, 0, -1];
            $this->phrase = '';
        }

        /**
         *
         */
        protected function addPhraseForQuery(): void
        {
            if (isset($this->phrases[$this->index][0])) {
                $this->phrase = '';
            }

            if ('?' === $this->previousToken) {
                $this->phrases[$this->index][1]++;
                $this->phrases[$this->index][2]++;
            } else {
                $this->phrases[++$this->index] = [$this->phrase, 1, 1];
                $this->phrase = '';
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
         * @param $subject
         *
         * @return Generator
         */
        protected function nextCharacter($subject): Generator
        {
            $length = ($this->stringFunctions->strlen)($subject);

            for ($i = 0; $i < $length; $i += 1) {
                yield $i => ($this->stringFunctions->substr)($subject, $i, 1);
            }
        }

        /**
         * @param string $pattern
         *
         * @return WildcardPerformer
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        public function get(string $pattern): WildcardPerformer
        {
            return static::$cachedPattern[$pattern]
                ?? (static::$cachedPattern[$pattern] = $this->create($pattern))
            ;
        }

        /**
         * @param string $token
         * @param string $nextToken
         *
         * @return bool
         */
        protected function analyseToken(string &$token, string $nextToken): bool
        {
            if (('?' === $token) && ('*' === $nextToken)) {
                $this->skipToken = true;
                $token = '?*';
            } elseif (('*' === $token) && ('*' === $nextToken)) {
                $this->skipToken = true;
                $token = '**';
            } elseif (("\\" === $token) && ($this->hasNextToken($nextToken))) {
                // Encode escaped character
                $this->skipToken = true;
                $token = chr(0) . $nextToken;
            } else {
                return false;
            }

            return true;
        }

        /**
         * @param string $token
         * @param string $nextToken
         * @param string $pattern
         * @param int $offset
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function analyseInvalidPreviousToken(string $token, string $nextToken, string $pattern, int $offset)
        {
            if (('?*' === $this->previousToken) && ('?' === $token)) {
                throw new InvalidCharacterForWildcardPattern($pattern, $offset);
            } elseif (('?*' === $this->previousToken) && ('*' === $token)) {
                throw new InvalidCharacterForWildcardPattern($pattern, $offset);
            } elseif (('**' === $this->previousToken) && ('*' === $token)) {
                throw new InvalidCharacterForWildcardPattern($pattern, $offset);
            } else {
                $this->analyseInvalidNextToken($token, $nextToken, $pattern, $offset);
            }
        }

        /**
         * @param string $token
         * @param string $nextToken
         * @param string $pattern
         * @param int $offset
         *
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function analyseInvalidNextToken(string $token, string $nextToken, string $pattern, int $offset)
        {
            if (('*' === $token) && ('?' === $nextToken)) {
                throw new InvalidCharacterForWildcardPattern($pattern, $offset);
            } elseif ("\\" === $token) {
                throw new InvalidEscapedCharacterForWildcardPattern($pattern, $offset);
            }
        }
    }
}
