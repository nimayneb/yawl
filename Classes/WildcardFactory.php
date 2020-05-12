<?php declare(strict_types=1);

namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.yawl".
     * See LICENSE.txt that was shipped with this package.
     */

    use Generator;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;
    use JayBeeR\Wildcard\Failures\InvalidEscapedCharacterForWildcardPattern;

    class WildcardFactory implements Encoding
    {
        use StringFunctionMapper;

        /**
         * @var WildcardPhraser[]
         */
        protected static array $cachedPattern = [];

        /**
         *
         */
        public function __construct()
        {
            $this->setSingleByte();
        }

        /**
         * @param string $pattern
         *
         * @return WildcardPhraser
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        protected function create(string $pattern): WildcardPhraser
        {
            $skipToken = false;
            $previousToken = null;
            $phrases = [];
            $phrase = '';
            $index = 0;

            foreach ($this->nextCharacter($pattern) as $offset => $token) {
                if ($skipToken) {
                    $skipToken = false;

                    continue;
                }

                $nextToken = ($this->substr)($pattern, $offset + 1, 1);

                if (('?' === $token) && ('*' === $nextToken)) {
                    $skipToken = true;
                    $token = '?*';
                } elseif (('*' === $token) && ('*' === $nextToken)) {
                    $skipToken = true;
                    $token = '**';
                } elseif (("\\" === $token) && ($this->hasNextToken($nextToken))) {
                    // Encode escaped character
                    $skipToken = true;
                    $token = chr(0) . $nextToken;
                } elseif ("\\" === $token) {
                    throw new InvalidEscapedCharacterForWildcardPattern($pattern, $offset);
                } elseif (('*' === $token) && ('?' === $nextToken)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $offset);
                } elseif (('?*' === $previousToken) && ('?' === $token)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $offset);
                } elseif (('?*' === $previousToken) && ('*' === $token)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $offset);
                } elseif (('**' === $previousToken) && ('*' === $token)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $offset);
                }

                switch ($token) {
                    case '?':
                    {
                        if (isset($phrases[$index][0])) {
                            $phrase = '';
                        }

                        if ('?' === $previousToken) {
                            $phrases[$index][1]++;
                            $phrases[$index][2]++;
                        } else {
                            $phrases[++$index] = [$phrase, 1, 1];
                            $phrase = '';
                        }

                        break;
                    }

                    case '*':
                    {
                        if (isset($phrases[$index][0])) {
                            $phrase = '';
                        }

                        $phrases[++$index] = [$phrase, 0, -1];
                        $phrase = '';

                        break;
                    }

                    case '**':
                    {
                        if (isset($phrases[$index][0])) {
                            $phrase = '';
                        }

                        $phrases[++$index] = [$phrase, 1, -1];
                        $phrase = '';

                        break;
                    }

                    case '?*':
                    {
                        if (isset($phrases[$index][0])) {
                            $phrase = '';
                        }

                        if ('?' === $previousToken) {
                            $phrases[$index][2]++;
                        } else {
                            $phrases[++$index] = [$phrase, 0, 1];
                            $phrase = '';
                        }


                        break;
                    }

                    default:
                    {
                        // Decode escaped character
                        if (chr(0) === $token[0]) {
                            $token = $token[1];
                        }

                        $phrase .= $token;

                        if (!isset($phrases[$index])) {
                            $phrases[$index] = [$phrase, 0, 0];
                        } else {
                            $phrases[$index][0] = $phrase;
                        }
                    }
                }

                $previousToken = $token;
            }

            $phraser = WildcardPhraser::get($phrases);
            $phraser->adopt($this);

            return $phraser;
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
            $length = ($this->strlen)($subject);

            for ($i = 0; $i < $length; $i += 1) {
                yield $i => ($this->substr)($subject, $i, 1);
            }
        }

        /**
         * @param string $pattern
         *
         * @return WildcardPhraser
         * @throws InvalidCharacterForWildcardPattern
         * @throws InvalidEscapedCharacterForWildcardPattern
         */
        public function get(string $pattern): WildcardPhraser
        {
            return static::$cachedPattern[$pattern]
                ?? (static::$cachedPattern[$pattern] = $this->create($pattern));
        }
    }
}
