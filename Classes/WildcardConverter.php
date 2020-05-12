<?php declare(strict_types=1);

namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.yawl".
     * See LICENSE.txt that was shipped with this package.
     */

    class WildcardConverter
    {
        /**
         * @param string $wildcard
         * @param string $subject
         *
         * @return bool
         */
        public static function singleByteMatch(string $wildcard, string $subject)
        {
            return (false !== preg_match(
                    static::convertWildcardToScopedRegularExpression($wildcard), $subject
                ));
        }

        /**
         * @param string $wildcard
         * @param string $subject
         *
         * @return bool
         */
        public static function multiByteMatch(string $wildcard, string $subject)
        {
            return mb_ereg_match(
                static::convertWildcardToRegularExpression($wildcard), $subject
            );
        }

        /**
         * @param string $wildcard
         *
         * @return string
         */
        public static function convertWildcardToScopedRegularExpression(string $wildcard)
        {
            return sprintf('/%s/', static::convertWildcardToRegularExpression($wildcard));
        }

        /**
         * @param string $wildcard
         *
         * @return string
         */
        public static function convertWildcardToRegularExpression(string $wildcard)
        {
            $escapeQuery = chr(0);
            $escapeAsterisk = chr(0) . chr(0);

            return sprintf(
                '^%s$',

                str_replace(
                    [$escapeAsterisk, $escapeQuery],
                    ['*', '?'],

                    str_replace(
                        ['\\?', '\\*', '?*', '?', '**', '*'],
                        ['\\' . $escapeQuery, '\\' . $escapeAsterisk, '.' . $escapeQuery, '.', '.+', '.*'],

                        $wildcard
                    )
                )
            );
        }
    }
} 