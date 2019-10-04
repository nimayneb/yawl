<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.yawl".
     * See LICENSE.txt that was shipped with this package.
     */

    interface Encoding
    {
        public function setSingleByte(): void;

        public function setMultiByte(string $encoding = 'UTF-8'): void;
    }
}
