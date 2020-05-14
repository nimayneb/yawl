<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

    /**
     *
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
