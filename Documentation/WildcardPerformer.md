Wildcard Performer
==================

This class gives the advantage of prepared patterns.

Example:

    <?php
    
        use JayBeeR\Wildcard\WildcardFactory;
        
        $factory = new WildcardFactory;
        $testPostfix = $factory->create('*test');
        
        if ($testPostfix->match('this is a small test')) {
            
        }
        
The strategy behind it is very simple:

Every token and phrase you have in a wildcard pattern will be split in a array structure with following information:

| Token  | Minimum position | Maximum position       |
|:------:|------------------|------------------------|
| *      | 0                | -1 (length of subject) | 
| ?      | 1                | 1                      | 
| **     | 1                | -1 (length of subject) |
| ?*     | 0                | 1                      |
| (none) | 0                | 0                      |

The array is structured like this:

    [
        'following phrase'
        'minimum position' (results from the tokens)
        'maximum position' (results from the tokens)
    ]

Each step of this information will be check with the current subject. The found position is then compared with these.


Implementation
--------------

The following number of implementation of PHP's internal string function is needed:

| Function | implements |
|----------|------------|
| strlen   | 5          |
| substr   | 4          |
| strpos   | 1          |
| chr      | 2          |
 
