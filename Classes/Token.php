<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.wildcard-trait".
     * See LICENSE.txt that was shipped with this package.
     */

    interface Token
    {
        // 0-1
        public const ZERO_OR_ONE_CHARACTER = '?*';

        // 0-x
        public const ZERO_OR_MANY_CHARACTERS = '*';

        // 1
        public const ONE_CHARACTER = '?';

        // 1-x
        public const MANY_OF_CHARACTERS = '**';

        public const ESCAPE_CHAR = '\\';
    }
}
