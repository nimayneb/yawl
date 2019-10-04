<?php namespace JayBeeR\Wildcard\Failures {

    /*
     * This file belongs to the package "nimayneb.wildcard-trait".
     * See LICENSE.txt that was shipped with this package.
     */

    use Exception;

    class InvalidEscapedCharacterForWildcardPattern extends Exception
    {
        public function __construct(string $reference, int $position)
        {
            $speakingClassName = substr(strrchr(static::class, "\\"), 1);
            $wordsFromClassName = array_filter(preg_split('/(?=[A-Z])/', $speakingClassName));
            $sentence = ucfirst(strtolower(implode(' ', $wordsFromClassName)));

            parent::__construct(sprintf('%s: "%s"', $sentence, $reference));
        }
    }
}
