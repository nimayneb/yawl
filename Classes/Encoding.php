<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.wildcard-trait".
     * See LICENSE.txt that was shipped with this package.
     */

    interface Encoding
    {
        public function setByte(): void;

        public function setMultiByte(string $encoding = 'UTF-8'): void;
    }
}
