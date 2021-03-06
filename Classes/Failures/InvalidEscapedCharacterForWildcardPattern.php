<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard\Failures {

    use Exception;

    /**
     * Exception for "Invalid escaped character for wildcard pattern"
     */
    class InvalidEscapedCharacterForWildcardPattern extends Exception
    {
        /**
         * Constructor
         *
         * @param string  $reference Named reference.
         * @param integer $position  Current position.
         */
        public function __construct(string $reference, int $position)
        {
            $speakingClassName = substr(strrchr(static::class, "\\"), 1);
            $wordsFromClassName = array_filter(preg_split('/(?=[A-Z])/', $speakingClassName));
            $sentence = ucfirst(strtolower(implode(' ', $wordsFromClassName)));

            parent::__construct(
                sprintf('%s: "%s" (position: %d)', $sentence, $reference, $position)
            );
        }
    }
}
