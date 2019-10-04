<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.wildcard-trait".
     * See LICENSE.txt that was shipped with this package.
     */

    use Closure;
    use Generator;
    use JayBeeR\Wildcard\Failures\InvalidCharacterForWildcardPattern;

    class WildcardPerformer
    {
        use Encoding;

        /**
         * @var array
         */
        protected array $matchesHandler = [];

        /**
         * @var WildcardPerformer[]
         */
        protected static array $cachedPattern = [];

        /**
         * @param string $pattern
         *
         * @throws InvalidCharacterForWildcardPattern
         */
        protected function __construct(string $pattern)
        {
            $skipToken = false;
            $previousToken = null;
            $phrase = '';
            $repeatingCharacters = 0;

            foreach ($this->nextCharacter($pattern) as $i => $token) {
                if ($skipToken) {
                    $skipToken = false;

                    continue;
                }

                $nextToken = ($this->substr)($pattern, $i + 1, 1);

                if (('?' === $token) && ('*' === $nextToken)) {
                    $skipToken = true;
                    $token = '?*';
                } elseif (('*' === $token) && ('*' === $nextToken)) {
                    $skipToken = true;
                    $token = '**';
                } elseif (('*' === $token) && ('?' === $nextToken)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $i);
                } elseif (('?*' === $previousToken) && ('?' === $token)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $i);
                } elseif (('?*' === $previousToken) && ('*' === $token)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $i);
                } elseif (('**' === $previousToken) && ('*' === $token)) {
                    throw new InvalidCharacterForWildcardPattern($pattern, $i);
                }

                $wildcardHandler = function (string $subject, int $i, ?Closure $nextHandler): ?array {
                    if (null === $nextHandler) {
                        $length = ($this->strlen)($subject);
                        $result = ['position' => ($length - 1), 'length' => ($length - $i) + 1];
                    } elseif (false === ($result = $nextHandler($subject, $i))) {
                        return null;
                    }

                    return $result;
                };

                switch ($token) {
                    case '?':
                    {
                        $repeatingCharacters++;

                        if ('?' !== $nextToken) {
                            $this->matchesHandler[] = [
                                'phrase' => $repeatingCharacters,
                                'handler' => fn (string $subject, int $i, int $offset) => $i + $offset
                            ];
                        }

                        break;
                    }

                    case '*':
                    {
                        $this->matchesHandler[] = [
                            'phrase' => null,
                            'handler' => function (string $subject, int $i, ?Closure $nextHandler) use($wildcardHandler): ?int {
                                ['length' => $length, 'position' => $position] = $wildcardHandler($subject, $i, $nextHandler);

                                return (0 <= $position) ? $i + $length : null;
                            }
                        ];

                        break;
                    }

                    case '**':
                    {
                        $this->matchesHandler[] = [
                            'phrase' => null,
                            'handler' => function (string $subject, int $i, ?Closure $nextHandler) use($wildcardHandler): ?int {
                                ['length' => $length, 'position' => $position] = $wildcardHandler($subject, $i, $nextHandler);

                                return (1 <= $position) ? $i + $length : null;
                            }
                        ];

                        break;
                    }

                    case '?*':
                    {
                        $this->matchesHandler[] = [
                            'phrase' => null,
                            'handler' => function (string $subject, int $i, ?Closure $nextHandler) use($wildcardHandler): ?int {
                                ['length' => $length, 'position' => $position] = $wildcardHandler($subject, $i, $nextHandler);

                                return (1 >= $position) ? $i + $position : null;
                            }
                        ];

                        break;
                    }

                    default:
                    {
                        if (('?' === $nextToken) && ('*' === $nextToken)) {
                            $this->matchesHandler[] = [
                                'phrase' => $phrase,
                                'handler' => fn (string $haystack, int $offset, $needle) => ($this->strpos)($haystack, $needle, $offset) - $offset
                            ];

                            $phrase = '';
                        } else {
                            $phrase .= $token;
                        }
                    }
                }

                if (!empty($phrase)) {
                    $this->matchesHandler[] = [
                        'phrase' => $phrase,
                        'handler' => fn (string $haystack, int $offset, $needle) => ($this->strpos)($haystack, $needle, $offset)
                    ];
                }

                $previousToken = $token;
            }
        }

        function nextCharacter($subject): Generator
        {
            $length = ($this->strlen)($subject);

            for ($i = 0; $i < $length; $i += 1) {
                yield $i => ($this->substr)($subject, $i, 1);
            }
        }

        public function hasMatch(string $subject): bool
        {
            $i = 0;
            $found = false;

            foreach ($this->matchesHandler as $index => ['handler' => $handler, 'phrase' => $phrase]) {
                if (!isset($subject[$i])) {
                    break;
                }

                if (!empty($phrase)) {
                    if (null === ($i = $handler($subject, $i, $phrase))) {
                        break;
                    }
                } else {
                    ['handler' => $nextHandler, 'phrase' => $phrase] = next($this->matchesHandler);

                    $providedHandler = null;

                    if (null !== $nextHandler) {
                        $providedHandler = function (string $subject, int $i) use ($nextHandler, $phrase): ?array {
                            $position = $nextHandler($subject, $i, $phrase);

                            return (false !== $position) ? ['length' => ($this->strlen)($phrase), 'position' => $position] : null;
                        };
                    }

                    if (null === ($i = $handler($subject, $i, $providedHandler))) {
                        break;
                    }
                }

                $found = true;
            }

            return $found;
        }

        /**
         * @param string $pattern
         *
         * @return WildcardPerformer
         * @throws InvalidCharacterForWildcardPattern
         */
        public static function get(string $pattern): WildcardPerformer
        {
            return static::$cachedPattern[$pattern] ?? (static::$cachedPattern[$pattern] = new static($pattern));
        }
    }
}
