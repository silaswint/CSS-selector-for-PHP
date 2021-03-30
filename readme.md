# CSS selector for PHP
## Installation

```
include "path_to_your_libraries/CSS-selector-for-PHP/jQuery.php";
```

## Simple use

```
$doc =
    (new Doc)
        ->setDoc("
            <ul>
                <li>this</li>
                <li>lorem</li>
                <li>is</li>
                <li>ipsum</li>
                <li>awesome</li>
                <li>dolor</li>
            </ul>
        ");

$query = $doc->_("ul :nth-of-type(odd)");
```
This will return a __DOMNodeList__ which you can loop by using foreach().
If the generated xPath string is invalid, it will return __false__.

Accepted setDoc() types:
- path to file (../file.html)
- HTML string
- DOMDocument
- DOMXPath
- DOMNode
- DOMElement
- DOMNodeList

## Supported CSS
### Pseudo classes

- empty
- first-child
- first-of-type
- has
- last-child
- last-of-type
- not
- nth-child
- nth-of-type
- only-child
- only-of-type
- optional
- read-only
- read-write
- required

### Attribute selectors

- \[attribute]
- \[attribute="value"]
- \[attribute~="value"]
- \[attribute|="value"]
- \[attribute^="value"]
- \[attribute="value"]
- \[attribute*="value"]

### Simple selectors

- \#id
- .class
- .class1.class2
- element.class
- element.class1.class2
- element#id
- \*
- element
- element, element

### Combinators

- descendant selector (space)
- child selector (>)
- adjacent sibling selector (+)
- general sibling selector (~)


### Extras

- friendly to whitespaces
- nth selector logic: 2n+1, 2, -n, etc.
- put "->prove()" before setDoc() to check how your browser handles your query
- $doc->getXPathAString() tells you the generated XPath string

### Information

It isn't safe for production mode right now.