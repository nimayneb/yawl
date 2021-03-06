<?php declare(strict_types=1);

/*
 * This file belongs to the package "nimayneb.yawl".
 * See LICENSE.txt that was shipped with this package.
 */

namespace JayBeeR\Wildcard {

    /**
     *
     */
    class StringFunctionsMapper
    {
        /**
         * @var callable
         */
        public $strlen;

        /**
         * @var callable
         */
        public $strpos;

        /**
         * @var callable
         */
        public $substr;

        protected ?string $encoding = null;

        /**
         *
         */
        public function __construct()
        {
            $this->setSingleByte();
        }

        /**
         *
         */
        public function setSingleByte(): void
        {
            $this->encoding = null;

            $this->strlen = 'strlen';
            $this->strpos = 'strpos';
            $this->substr = 'substr';
        }

        /**
         * @param string $encoding
         */
        public function setMultiByte(string $encoding = 'UTF-8'): void
        {
            $this->encoding = $encoding;

            $this->strlen = fn(string $string)
                => mb_strlen($string, $this->encoding);
            $this->strpos = fn(string $haystack, string $needle, int $offset = 0)
                => mb_strpos($haystack, $needle, $offset, $this->encoding);
            $this->substr = fn(string $string, int $start, int $length = null)
                => mb_substr($string, $start, $length, $this->encoding);
        }
    }
}
