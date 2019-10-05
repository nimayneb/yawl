<?php

use JayBeeR\Wildcard\Encoding;
use JayBeeR\Wildcard\Tests\WildcardMatcherTest;
use JayBeeR\Wildcard\WildcardConverter;
use JayBeeR\Wildcard\WildcardMatcher;
use JayBeeR\Wildcard\WildcardPerformer;

require_once '../../vendor/autoload.php';
require_once 'Benchmarker.inc';

{
    function generateRandomString(int $length): string
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    function generateRandomWildcard(string $subject): string
    {
        $wildcard = '';
        $onlyPhrase = false;
        $start = true;

        while (!empty($subject)) {
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

    function generateRandomWildcards(int $count)
    {
        $temp = [];

        for ($i = 1; $i <= $count; $i++) {
            $subject = generateRandomString(random_int(1, 256));
            $wildcard = generateRandomWildcard($subject);

            $temp[] = [
                $subject,
                $wildcard,
                WildcardConverter::convertWildcardToRegularExpression($wildcard)
            ];
        }

        return $temp;
    }
}