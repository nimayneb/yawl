<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard\Tests\Helper {

    use Exception;
    use Generator;

    class WildcardGenerator
    {
        /**
         * @param int $length
         * @param string $availableCharacters
         *
         * @return string
         */
        public static function getRandomString(int $length, string $availableCharacters): string
        {
            return substr(
                str_shuffle(
                    str_repeat(
                        $availableCharacters,
                        (int)ceil($length / strlen($availableCharacters))
                    )
                ),
                1,
                $length
            );
        }

        /**
         * @param string $subject
         *
         * @return string
         * @throws Exception
         */
        public static function getRandom(string $subject): string
        {
            $wildcard = '';
            $onlyPhrase = false;
            $start = true;

            while ('' !== $subject && null !== $subject) {
                $token = random_int($start ? 0 : 1, 32);

                if (($token === 0) || ($onlyPhrase)) {
                    $onlyPhrase = false;
                    $cut = static::appendWord($subject, $wildcard);
                } else {
                    $onlyPhrase = true;

                    switch ($token % 4) {
                        case 1: {
                            $cut = static::appendQueriesAsterisk($subject, $wildcard);

                            break;
                        }

                        case 2: {
                            $cut = static::appendAsterisks($subject, $wildcard);

                            break;
                        }

                        case 3: {
                            $cut = static::appendQueries($subject, $wildcard);

                            break;
                        }

                        default: {
                            $cut = static::appendAsterisk($subject, $wildcard);

                            break;
                        }
                    }
                }

                $subject = substr($subject, $cut);
                $start = false;
            }

            return $wildcard;
        }

        /**
         * @param string $subject
         * @param string $wildcard
         *
         * @return int
         * @throws Exception
         */
        protected static function appendWord(string $subject, string &$wildcard): int
        {
            $cut = random_int(1, max(1, (int)(strlen($subject) / 2)));
            $wildcard .= substr($subject, 0, $cut);

            return $cut;
        }

        /**
         * @param string $subject
         * @param string $wildcard
         *
         * @return int
         * @throws Exception
         */
        protected static function appendAsterisk(string $subject, string &$wildcard): int
        {
            $wildcard .= '*';

            $empty = random_int(0, 1);
            $reallyEmpty = random_int(0, 1);

            if ($empty === $reallyEmpty) {
                $cut = 0;
            } else {
                $cut = random_int(1, strlen($subject));
            }

            return $cut;
        }

        /**
         * @param string $subject
         * @param string $wildcard
         *
         * @return int
         * @throws Exception
         */
        protected static function appendQueries(string $subject, string &$wildcard): int
        {
            $cut = random_int(1, min(strlen($subject), 5));
            $wildcard .= str_repeat('?', $cut);

            return $cut;
        }

        /**
         * @param string $subject
         * @param string $wildcard
         *
         * @return int
         * @throws Exception
         */
        protected static function appendAsterisks(string $subject, string &$wildcard): int
        {
            $cut = random_int(1, strlen($subject));
            $wildcard .= '**';

            return $cut;
        }

        /**
         * @param string $subject
         * @param string $wildcard
         *
         * @return int
         * @throws Exception
         */
        protected static function appendQueriesAsterisk(string $subject, string &$wildcard): int
        {
            $empty = random_int(0, 1);
            $reallyEmpty = random_int(0, 1);

            if ($empty === $reallyEmpty) {
                $cut = 0;
                $wildcard .= '?*';
            } else {
                $cut = random_int(1, min(strlen($subject), 5));
                $wildcard .= str_repeat('?', $cut) . '*';
            }

            return $cut;
        }

        /**
         * @param int $count
         * @param string $availableCharacters
         *
         * @return Generator
         * @throws Exception
         */
        public static function getRandomCount(
            int $count,
            string $availableCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        ): Generator
        {
            for ($i = 1; $i <= $count; $i++) {
                $subject = static::getRandomString(random_int(1, 256), $availableCharacters);
                $wildcard = static::getRandom($subject);

                yield $i => [
                    'subject' => $subject,
                    'wildcard' => $wildcard,
                ];
            }
        }
    }
}