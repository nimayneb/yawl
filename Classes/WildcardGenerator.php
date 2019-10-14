<?php namespace JayBeeR\Wildcard {

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
            return substr(str_shuffle(str_repeat($x = $availableCharacters, ceil($length / strlen($x)))), 1, $length);
        }

        /**
         * @param string $subject
         * @return string
         * @throws Exception
         */
        public static function getRandom(string $subject): string
        {
            $wildcard = '';
            $onlyPhrase = false;
            $start = true;

            while ('' !== $subject && null !== $subject) {
                $token = random_int($start ? 0 : 1, 16);

                if (($token === 0) || ($onlyPhrase)) {
                    $onlyPhrase = false;
                    $cut = random_int(1, max(1, (int) strlen($subject) / 2));
                    $wildcard .= substr($subject, 0, $cut);
                } else {
                    $onlyPhrase = true;

                    switch ($token) {
                        default: {
                            $wildcard .= '*';

                            $empty = random_int(0, 1);
                            $reallyEmpty = random_int(0, 1);

                            if ($empty === $reallyEmpty) {
                                $cut = 0;
                            } else {
                                $cut = random_int(1, strlen($subject));
                            }

                            break;
                        }

                        case 1:
                        case 4:
                        case 8:
                        case 11: {
                            $cut = random_int(1, min(strlen($subject), 5));
                            $wildcard .= str_repeat('?', $cut);

                            break;
                        }

                        case 13:
                        case 14:
                        case 7:
                        case 16: {
                            $cut = random_int(1, strlen($subject));
                            $wildcard .= '**';

                            break;
                        }

                        case 2:
                        case 12:
                        case 15: {
                            $empty = random_int(0, 1);
                            $reallyEmpty = random_int(0, 1);

                            if ($empty === $reallyEmpty) {
                                $cut = 0;
                                $wildcard .= '?*';
                            } else {
                                $cut = random_int(1, min(strlen($subject), 5));
                                $wildcard .= str_repeat('?', $cut) . '*';
                            }

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
         * @param int $count
         * @param string $availableCharacters
         *
         * @return Generator
         * @throws Exception
         */
        public static function getRandomCount(int $count, string $availableCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): Generator
        {
            for ($i = 1; $i <= $count; $i++) {
                $subject = static::getRandomString(random_int(1, 256), $availableCharacters);
                $wildcard = static::getRandom($subject);

                yield $i => [
                    'subject' => $subject,
                    'wildcard' => $wildcard
                ];
            }
        }
    }
}