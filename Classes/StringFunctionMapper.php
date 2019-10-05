<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.yawl".
     * See LICENSE.txt that was shipped with this package.
     */

    trait StringFunctionMapper
    {
        /**
         * @var callable
         */
        protected $strlen;

        /**
         * @var callable
         */
        protected $strpos;

        /**
         * @var callable
         */
        protected $substr;

        protected ?string $encoding = null;

        public function setSingleByte(): void
        {
            $this->encoding = null;

            $this->strlen = 'strlen';
            $this->strpos = 'strpos';
            $this->substr = 'substr';
        }

        public function setMultiByte(string $encoding = 'UTF-8'): void
        {
            $this->encoding = $encoding;

            $this->strlen = fn (string $string) => mb_strlen($string, $this->encoding);
            $this->strpos = fn (string $haystack, string $needle, int $offset = 0) => mb_strpos($haystack, $needle, $offset, $this->encoding);
            $this->substr = fn (string $string, int $start, int $length = null) => mb_substr($string, $start, $length, $this->encoding);
        }
    }
}
