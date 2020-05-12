<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

    class WildcardState
    {
        public string $subject;

        public bool $canBeZero;

        public int $maxPosition;

        public int $subjectLength;

        public bool $dynamicLength;

        public bool $found;

        /**
         * @param string $subject
         */
        public function __construct(string $subject)
        {
            $this->subject = $subject;
        }
    }
}
