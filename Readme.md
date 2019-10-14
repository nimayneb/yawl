YAWL - Yet another wildcard library
===================================

This is a library with classes for any wildcard implementation that finds pattern with * (asterisk) and ? (query) token.


Problem
-------

Without regular expression extension (named `ext-pcre`) you have no wildcard support within PHP.

The known wildcard behavior (see [Wildcard character (@Wikipedia)](https://en.wikipedia.org/wiki/Wildcard_character))
is limited for a good implementation.

A small remedy?

We need a kind of "compiled" and "cached" pattern. The implementation of regular expression like `mb_ereg_match` has a
huge performance (we lost)!


Table of content
----------------

1. Benchmark
2. Wildcard variants
3. Possible valid pattern
4. Invalid pattern
5. Escaping 
6. Repeating phrases
7. Caching
8. Wish list

API:

1. [Matcher (for use of single calls)](Documentation/WildcardMatcher.md)
2. [Phraser (for use of multiple calls)](Documentation/WildcardPhraser.md)
3. [Converter (for use of regular expression)](Documentation/WildcardConverter.md)
4. [Generator (for test purposes)](Documentation/WildcardGenerator.md)


Benchmark
---------

When we benchmark several methods to match a fitting phrase (1000 random strings), we have a good results without
"regular expressions":


*Single Byte:*

| Benchmark           | Time       | Reference | Difference  |   
|---------------------|------------|:---------:|:-----------:|
|   `WildcardPhraser` | 0.00353789 |   100 %   | -           |
|        `preg_match` | 0.01223898 |    71 %   | 71 %        |
|   `WildcardMatcher` | 0.01635289 |    25 %   | 78 %        |


*Multi Byte (like Unicode):*

| Benchmark           | Time       | Reference | Difference  |  
|---------------------|------------|:---------:|:-----------:|
|   `WildcardPhraser` | 0.00660086 |   100 %   | -           | 
|     `mb_ereg_match` | 0.01290488 |    48 %   | 48 %        | 
|   `WildcardMatcher` | 0.03252506 |    60 %   | 79 %        | 
   
   
Wildcard variants
-----------------

| None character | One character | Two characters | Token          |
|----------------|---------------|----------------|----------------|
|              0 |             0 |              0 | (null)         |
|              0 |             0 |              1 | ??             |
|              0 |             1 |              0 | ?              |
|              0 |             1 |              1 | **             |
|              1 |             0 |              0 | (empty string) |
|              1 |             0 |              1 | ??** (draft)   |
|              1 |             1 |              0 | ?*             |
|              1 |             1 |              1 | *              |


Possible valid pattern
----------------------

        ??      (2 characters)
        ???*  (2-3 characters)


Invalid pattern
---------------

        ***
        ?**
        ?*?
        *?

                           
Escaping
--------

        \\
        \?
        \*

Please note of this escaping scenarios:

| Subject        | Pattern       | Explanation                                                                                                    |
|----------------|---------------|----------------------------------------------------------------------------------------------------------------|
| `search\\*`    | `*\\*`        | matches `search\ ` with wildcard, then matches escaped `*`                                                     |
| `search\\*`    | `*\\\\*`      | matches `search` with wildcard, then matches escaped `\ `, then matches `*` with wildcard                      |
| `search\\*`    | `*\\\\\\*`    | matches `search` with wildcard, then matches escaped `\ `, then matches escaped `*`                            |
| `search\\*`    | `*\\\\\\\\*`  | matches `search` with wildcard, then matches escaped `\ `, then not matches escaped `\ ` - ignore rest of `*`  |


Further explanation:

| programmatic   | internal  | after escaping |
|----------------|-----------|----------------|
| `\\`           | `\ `      | `\ `           |
| `\\\\`         | `\\`      | `\ `           |

Repeating phrases
-----------------

The asterisk (`*`) has a problem finding the right position. If several identical phrases are found in a string, it is
necessary to return to a position of previous token (`*`) and continue to try from the next found position.

     Search: *is?ue
    Subject: this is an asterisk issue
     Founds:   ^  ^          ^   ^


Caching
-------

The regular expression extension has a caching and compiling strategy to improve performance. The second time the same pattern is
called, a tremendous increase in performance is achieved.

To help us to improve also performance, we use a simple key-value caching.

        protected array $cachedResults = [];

        public function match(string $subject): bool
        {
            return $this->cachedResults[$subject] ?? $this->cachedResults[$subject] = $this->computePhrases($subject, 0);
        }

This can be decapsulated, so that we can provide a Redis implementation for example.


Wish list
---------

- Caching interface (Redis performance, memory allocation)
- Combine Matcher and Phraser (two advantages in one)
- Remove of StringFunctionMapper (too slow)
- New pattern `??**` (0 or 2 characters) or `?????**` (0 or 5 characters)
- [Glob support](https://en.wikipedia.org/wiki/Glob_\(programming\))
    - Example: `[abcdef0123456789]`, `[0-9a-f]`, `[!a-z]`