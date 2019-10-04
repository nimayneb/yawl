<?php namespace JayBeeR\Wildcard {

    /*
     * This file belongs to the package "nimayneb.wildcard-trait".
     * See LICENSE.txt that was shipped with this package.
     */

    use Closure;

    trait EncodingFunctionMapper
    {
        protected Closure $strlen;

        protected Closure $strpos;

        protected Closure $substr;

        protected Closure $chr;

        protected ?string $encoding = null;

        public function setByte(): void
        {
            $this->encoding = null;

            $this->strlen = fn (string $string) => strlen($string);
            $this->strpos = fn (string $haystack, string $needle, int $offset = 0) => strpos($haystack, $needle, $offset);
            $this->substr = fn (string $string, int $start, int $length = null) => substr($string, $start, $length);
            $this->chr = fn (string $ascii) => chr($ascii);
        }

        public function setMultiByte(string $encoding = 'UTF-8'): void
        {
            $this->encoding = $encoding;

            $this->strlen = fn (string $string) => mb_strlen($string, $this->encoding);
            $this->strpos = fn (string $haystack, string $needle, int $offset = 0) => mb_strpos($haystack, $needle, $offset, $this->encoding);
            $this->substr = fn (string $string, int $start, int $length = null) => mb_substr($string, $start, $length, $this->encoding);
            $this->chr = fn (string $cp) => mb_chr($cp, $this->encoding);
        }
    }
}
