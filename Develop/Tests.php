<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

include __DIR__ . "/../jQuery.php";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        html, body {
            font-family: Arial, serif;
        }
    </style>
</head>
<body>
<?php
function Dom2Array($root) {
    $array = array();

    //list attributes
    if($root->hasAttributes()) {
        foreach($root->attributes as $attribute) {
            $array['_attributes'][$attribute->name] = $attribute->value;
        }
    }

    //handle classic node
    if($root->nodeType == XML_ELEMENT_NODE) {
        $array['_type'] = $root->nodeName;
        if($root->hasChildNodes()) {
            $children = $root->childNodes;
            for($i = 0; $i < $children->length; $i++) {
                $child = Dom2Array( $children->item($i) );

                //don't keep textnode with only spaces and newline
                if(!empty($child)) {
                    $array['_children'][] = $child;
                }
            }
        }

        //handle text node
    } elseif($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
        $value = $root->nodeValue;
        if(!empty($value)) {
            $array['_type'] = '_text';
            $array['_content'] = $value;
        }
    }

    return $array;
}

function var_dump_to_string($object) {
    ob_start();
    var_dump($object);
    $result = ob_get_clean();
    return $result;
}

function toArray($object) {
    $array = array();
    foreach ($object as $key => $value) {
        $array[]["nodeValue"] = $value->nodeValue;
        $array[]["textContent"] = $value->textContent;
        $array[]["tagName"] = $value->tagName;

        if ($value->hasAttributes()) {
            foreach ($value->attributes as $attr) {
                $attribute_name = $attr->nodeName;
                $attribute_value = $attr->nodeValue;

                $array[]["attributes"][] = [
                    "name" => $attribute_name,
                    "value" => $attribute_value
                ];
            }
        }
    }

    return $array;
}

function tests_section($title, $html, $query_string, $expected_result, $output = false) {
    echo "<h2>" . $title . "</h2>";

    $doc = (new Doc);

    if($output === true) {
        $doc = $doc->prove();
    }

    $doc = $doc->setDoc($html);

    $query = $doc->_($query_string);

    if($output === true) {
    ?>

    <h3>Selected nodes</h3>
    This is the xPath string:<br />
    <textarea style="width: 500px; height: 30px" readonly><?php echo $doc->getXPathAsString(); ?></textarea><br /><br />

    <?php
    }

    if($query === false) {
        die("xPath has wrong syntax");
    }

    if($output === true) {
        echo "<h4>Array</h4>";
        echo "<pre>";
        var_dump($doc->getQueryStringArray());
        echo "</pre>";

        echo "<h4>List</h4>";
    ?>

    <ul>
        <?php
        if(count($query) == 0) {
            echo "<li>(no results)</li>";
        }

        foreach ($query as $element) {
            echo "<li><pre>" . var_dump_to_string(Dom2Array($element)) . "</pre></li>";
        }
        ?>
    </ul>
    <?php
    }

    $result = json_encode(toArray($query));


    if($result === $expected_result) {
        echo "<b style='color: green'>Fine</b>";
    } else {
        echo "<b style='color: red'>ERROR</b>";
        echo "<br />Result should be:<br /><textarea style='width: 500px; height: 100px' readonly>" . $result . "</textarea>";
        die();
    }
}

/*tests_section(
    "Invalid HTML",
    "<ul><ol>Test</ol></ul>",
    "ul ol",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"ol"}]',
    true
);*/

tests_section(
    "Single element",
    "<div>Test</div>",
    "div",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"}]'
);

tests_section(
    "Class after single element",
    "<div class='example-class'>Test</div>",
    "div.example-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class"}]}]'
);

tests_section(
    "Multiple classes after single element #1",
    "<div class='example-class'>Test</div>",
    "div.example-class.second-class",
    '[]'
);

tests_section(
    "Multiple classes after single element #2",
    "<div class='example-class second-class'>Test</div>",
    "div.example-class.second-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class second-class"}]}]'
);

tests_section(
    "Multiple classes after single element #3",
    "<div class='second-class example-class'>Test</div>",
    "div.example-class.second-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"second-class example-class"}]}]'
);

tests_section(
    "Multiple classes after single element #4",
    "<div class=' second-class  example-class '>Test</div>",
    "div.example-class.second-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":" second-class  example-class "}]}]'
);

tests_section(
    "ID after single element #1",
    "<div id='example-id'>Test</div>",
    "div#example-id",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"id","value":"example-id"}]}]'
);

tests_section(
    "ID after single element #2",
    "<div class='example-id'>Test</div>",
    "div#example-id",
    '[]'
);

tests_section(
    "Single class",
    "<div class='example-class'>Test</div>",
    ".example-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class"}]}]'
);

tests_section(
    "Single id",
    "<div id='example-id'>Test</div>",
    "#example-id",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"id","value":"example-id"}]}]'
);

tests_section(
    "Single class after *",
    "<div class='example-class'>Test</div>",
    "*.example-class",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"class","value":"example-class"}]}]'
);

tests_section(
    "Single id after *",
    "<div id='example-id'>Test</div>",
    "*#example-id",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"},{"attributes":[{"name":"id","value":"example-id"}]}]'
);

tests_section(
    "Single *",
    "<div>Test</div>",
    "*",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"div"}]'
);

tests_section(
    "Combinator: Descendant selector #1",
    "<div><p>Test</p></div>",
    "div p",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"p"}]'
);

tests_section(
    "Combinator: Descendant selector #2",
    "<div class='first'><div class='second'><p>Test</p></div></div>",
    "div p",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"p"}]'
);

tests_section(
    "Combinator: Descendant selector #3",
    "<div><span class='first'>A<span class='second'>B</span></p></div>",
    "div span",
    '[{"nodeValue":"AB"},{"textContent":"AB"},{"tagName":"span"},{"attributes":[{"name":"class","value":"first"}]},{"nodeValue":"B"},{"textContent":"B"},{"tagName":"span"},{"attributes":[{"name":"class","value":"second"}]}]'
);

tests_section(
    "Combinator: Child selector #1",
    "<ul><li>Test</li></ul>",
    "ul > li",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"li"}]'
);

tests_section(
    "Combinator: Child selector #2",
    "<ul><li>Test</li></ul>",
    "ul  >  li",
    '[{"nodeValue":"Test"},{"textContent":"Test"},{"tagName":"li"}]'
);

tests_section(
    "Combinator: Child selector #3",
    "<ul><li><span>Test</span></li></ul>",
    "ul > span",
    '[]'
);

tests_section(
    "Combinator: Adjacent sibling selector",
    "<p>The + selector is used to select an element that is directly after another specific element.</p>
<p>The following example selects the first p element that are placed immediately after div elements:</p>

<div>
  <p>Paragraph 1 in the div.</p>
  <p>Paragraph 2 in the div.</p>
</div>

<p>Paragraph 3. After a div.</p>
<p>Paragraph 4. After a div.</p>

<div>
  <p>Paragraph 5 in the div.</p>
  <p>Paragraph 6 in the div.</p>
</div>

<p>Paragraph 7. After a div.</p>
<p>Paragraph 8. After a div.</p>",
    "div + p",
    '[{"nodeValue":"Paragraph 3. After a div."},{"textContent":"Paragraph 3. After a div."},{"tagName":"p"},{"nodeValue":"Paragraph 7. After a div."},{"textContent":"Paragraph 7. After a div."},{"tagName":"p"}]'
);

tests_section(
    "Combinator: General sibling selector",
    "<p>The general sibling selector (~) selects all elements that are siblings of a specified element.</p>

<p>Paragraph 1.</p>

<div>
  <p>Paragraph 2.</p>
</div>

<p>Paragraph 3.</p>
<code>Some code.</code>
<p>Paragraph 4.</p>",
    "div ~ p",
    '[{"nodeValue":"Paragraph 3."},{"textContent":"Paragraph 3."},{"tagName":"p"},{"nodeValue":"Paragraph 4."},{"textContent":"Paragraph 4."},{"tagName":"p"}]',
);

tests_section(
    "Comma selector",
    "<h1>Welcome to My Homepage</h1>

<div>
  <p>My name is Donald.</p>
  <p>I live in Duckburg.</p>
</div>

<p>My best friend is Mickey.</p>",
    "h1, p",
    '[{"nodeValue":"Welcome to My Homepage\r\n  My name is Donald.\r\n  I live in Duckburg.\r\nMy best friend is Mickey."},{"textContent":"Welcome to My Homepage\r\n  My name is Donald.\r\n  I live in Duckburg.\r\nMy best friend is Mickey."},{"tagName":"h1"},{"nodeValue":"My name is Donald."},{"textContent":"My name is Donald."},{"tagName":"p"},{"nodeValue":"I live in Duckburg."},{"textContent":"I live in Duckburg."},{"tagName":"p"},{"nodeValue":"My best friend is Mickey."},{"textContent":"My best friend is Mickey."},{"tagName":"p"}]'
);

tests_section(
    "[attribute] selector",
    '<p>The links with a target attribute gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target]",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]},{"nodeValue":"wikipedia.org"},{"textContent":"wikipedia.org"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.wikipedia.org"}]},{"attributes":[{"name":"target","value":"_top"}]}]',
);

tests_section(
    "[attribute=value] selector #1",
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target=_blank]",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    '[attribute="value"] selector #2',
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    'a[target="_blank"]',
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    "[attribute='value'] selector #3",
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target="_blank">disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target='_blank']",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    "[attribute='va\"lue'] selector #4",
    '<p>The link with target="_blank" gets a yellow background:</p>

<a href="https://www.w3schools.com">w3schools.com</a>
<a href="http://www.disney.com" target=\'_"blank\'>disney.com</a>
<a href="http://www.wikipedia.org" target="_top">wikipedia.org</a>',
    "a[target='_\"blank']",
    '[{"nodeValue":"disney.com"},{"textContent":"disney.com"},{"tagName":"a"},{"attributes":[{"name":"href","value":"http:\/\/www.disney.com"}]},{"attributes":[{"name":"target","value":"_\"blank"}]}]'
);

tests_section(
    "[attribute~=value] selector",
    '<p>The image with the title attribute containing the word "flower" gets a yellow border.</p>

<img src="klematis.jpg" title="klematis flower" width="150" height="113">
<img src="img_flwr.gif" title="flowers" width="224" height="162">
<img src="landscape.jpg" title="landscape" width="160" height="120">',
    "[title~=flower]",
    '[{"nodeValue":""},{"textContent":""},{"tagName":"img"},{"attributes":[{"name":"src","value":"klematis.jpg"}]},{"attributes":[{"name":"title","value":"klematis flower"}]},{"attributes":[{"name":"width","value":"150"}]},{"attributes":[{"name":"height","value":"113"}]}]'
);

/*tests_section(
    "[attribute|=value] selector",
    '<p lang="en">Hello!</p>
<p lang="en-us">Hi!</p>
<p lang="en-gb">Ello!</p>
<p lang="us">Hi!</p>
<p lang="no">Hei!</p>',
    "[lang|=en]",
    '[{"nodeValue":""},{"textContent":""},{"tagName":"img"},{"attributes":[{"name":"src","value":"klematis.jpg"}]},{"attributes":[{"name":"title","value":"klematis flower"}]},{"attributes":[{"name":"width","value":"150"}]},{"attributes":[{"name":"height","value":"113"}]}]',
    true
);*/

tests_section(
    "[attribute|=value] selector",
    '<div><p lang="en">Hello!</p>
<p lang="en-us">Hi!</p>
<p lang="en-gb">Ello!</p>
<p lang="us">Hi!</p>
<p lang="no">Hei!</p></div>',
    "[lang|=en]",
    '[{"nodeValue":"Hello!"},{"textContent":"Hello!"},{"tagName":"p"},{"attributes":[{"name":"lang","value":"en"}]},{"nodeValue":"Hi!"},{"textContent":"Hi!"},{"tagName":"p"},{"attributes":[{"name":"lang","value":"en-us"}]},{"nodeValue":"Ello!"},{"textContent":"Ello!"},{"tagName":"p"},{"attributes":[{"name":"lang","value":"en-gb"}]}]'
);

tests_section(
    "CSS [attribute^=value] Selector",
    '<body><div class="first_test">The first div element.</div>
<div class="second">The second div element.</div>
<div class="test">The third div element.</div>
<div class="test_ex">The fourth div element.</div>
<p class="test">This is a paragraph.</p>
<p class="test_ex">This is a paragraph.</p>
</body>',
    'div[class^="test"]',
    '[{"nodeValue":"The third div element."},{"textContent":"The third div element."},{"tagName":"div"},{"attributes":[{"name":"class","value":"test"}]},{"nodeValue":"The fourth div element."},{"textContent":"The fourth div element."},{"tagName":"div"},{"attributes":[{"name":"class","value":"test_ex"}]}]'
);

tests_section(
    "CSS [attribute$=value] Selector",
    '<body>

<div class="first_test">The first div element.</div>
<div class="second">The second div element.</div>
<div class="test">The third div element.</div>
<p class="test">This is some text in a paragraph.</p>

</body>',
    'div[class$="test"]',
    '[{"nodeValue":"The first div element."},{"textContent":"The first div element."},{"tagName":"div"},{"attributes":[{"name":"class","value":"first_test"}]},{"nodeValue":"The third div element."},{"textContent":"The third div element."},{"tagName":"div"},{"attributes":[{"name":"class","value":"test"}]}]'
);

tests_section(
    "CSS [attribute*=value] Selector",
    '<body>

<div class="first_test">The first div element.</div>
<div class="second">The second div element.</div>
<div class="test">The third div element.</div>
<p class="test">This is some text in a paragraph.</p>

</body>',
    'div[class*="test"]',
    '[{"nodeValue":"The first div element."},{"textContent":"The first div element."},{"tagName":"div"},{"attributes":[{"name":"class","value":"first_test"}]},{"nodeValue":"The third div element."},{"textContent":"The third div element."},{"tagName":"div"},{"attributes":[{"name":"class","value":"test"}]}]'
);

tests_section(
    "CSS :empty Selector",
    '<body>

<p></p>
<p>A paragraph.</p>
<p>Another paragraph.</p>

</body>',
    'p:empty',
    '[{"nodeValue":""},{"textContent":""},{"tagName":"p"}]'
);

tests_section(
    "CSS :first-child Selector",
    '<body>

<p>This paragraph is the first child of its parent (body).</p>

<h1>Welcome to My Homepage</h1>
<p>This paragraph is not the first child of its parent.</p>

<div>
  <p>This paragraph is the first child of its parent (div).</p>
  <p>This paragraph is not the first child of its parent.</p>
</div>

</body>',
    'p:first-child',
    '[{"nodeValue":"This paragraph is the first child of its parent (body)."},{"textContent":"This paragraph is the first child of its parent (body)."},{"tagName":"p"},{"nodeValue":"This paragraph is the first child of its parent (div)."},{"textContent":"This paragraph is the first child of its parent (div)."},{"tagName":"p"}]'
);

tests_section(
    "CSS :first-of-type Selector",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:first-of-type',
    '[{"nodeValue":"The first paragraph."},{"textContent":"The first paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :has Selector",
    '<body>

<a>Not this</a>
<a>This <img /></a>
<h1>Not this</h1>
<h1>This</h1>
<p>Abc</p>
</body>',
    'a:has(> img), h1:has(+ p)',
    '[{"nodeValue":"This "},{"textContent":"This "},{"tagName":"a"},{"nodeValue":"This"},{"textContent":"This"},{"tagName":"h1"}]'
);

tests_section(
    "CSS :last-child Selector",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:last-child',
    '[{"nodeValue":"The fourth paragraph."},{"textContent":"The fourth paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :last-of-type Selector",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:last-of-type',
    '[{"nodeValue":"The fourth paragraph."},{"textContent":"The fourth paragraph."},{"tagName":"p"}]'
);

tests_section(
    "Spaces",
    '<body>

<div>not okay</div>
<p>okay</p>
<span>not okay</span>

</body>',
    '   p  ',
    '[{"nodeValue":"okay"},{"textContent":"okay"},{"tagName":"p"}]'
);

tests_section(
    "CSS :not Selector",
    '<body>

<h1>This is a heading</h1>

<p>This is a paragraph.</p>
<p>This is another paragraph.</p>

<div>This is some text in a div element.</div>

<a href="https://www.w3schools.com" target="_blank">Link to W3Schools!</a>

</body>',
    ':not(p)',
    '[{"nodeValue":"\r\n\r\nThis is a heading\r\n\r\nThis is a paragraph.\r\nThis is another paragraph.\r\n\r\nThis is some text in a div element.\r\n\r\nLink to W3Schools!\r\n\r\n"},{"textContent":"\r\n\r\nThis is a heading\r\n\r\nThis is a paragraph.\r\nThis is another paragraph.\r\n\r\nThis is some text in a div element.\r\n\r\nLink to W3Schools!\r\n\r\n"},{"tagName":"body"},{"nodeValue":"This is a heading"},{"textContent":"This is a heading"},{"tagName":"h1"},{"nodeValue":"This is some text in a div element."},{"textContent":"This is some text in a div element."},{"tagName":"div"},{"nodeValue":"Link to W3Schools!"},{"textContent":"Link to W3Schools!"},{"tagName":"a"},{"attributes":[{"name":"href","value":"https:\/\/www.w3schools.com"}]},{"attributes":[{"name":"target","value":"_blank"}]}]'
);

tests_section(
    "CSS :nth-child Selector #1",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:nth-child(2)',
    '[{"nodeValue":"The second paragraph."},{"textContent":"The second paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-child Selector #2",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:nth-child(2n+1)',
    '[{"nodeValue":"The first paragraph."},{"textContent":"The first paragraph."},{"tagName":"p"},{"nodeValue":"The third paragraph."},{"textContent":"The third paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-child Selector #3",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:nth-child(even)',
    '[{"nodeValue":"The second paragraph."},{"textContent":"The second paragraph."},{"tagName":"p"},{"nodeValue":"The fourth paragraph."},{"textContent":"The fourth paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-child Selector #4",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:nth-child(odd)',
    '[{"nodeValue":"The first paragraph."},{"textContent":"The first paragraph."},{"tagName":"p"},{"nodeValue":"The third paragraph."},{"textContent":"The third paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-child Selector #5",
    '<body>
    <p>1</p>
    <p>2</p>
    <p>3</p>
    <p>4</p>
    <p>5</p>
    <p>6</p>
    <p>7</p>
    <p>8</p>
    <p>9</p>
    <p>10</p>
</body>',
    'p:nth-child(n+6)',
    '[{"nodeValue":"6"},{"textContent":"6"},{"tagName":"p"},{"nodeValue":"7"},{"textContent":"7"},{"tagName":"p"},{"nodeValue":"8"},{"textContent":"8"},{"tagName":"p"},{"nodeValue":"9"},{"textContent":"9"},{"tagName":"p"},{"nodeValue":"10"},{"textContent":"10"},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-child Selector #6",
    '<body>
    <p>1</p>
    <p>2</p>
    <p>3</p>
    <p>4</p>
    <p>5</p>
    <p>6</p>
    <p>7</p>
    <p>8</p>
    <p>9</p>
    <p>10</p>
</body>',
    'p:nth-child(-n+3)',
    '[{"nodeValue":"1"},{"textContent":"1"},{"tagName":"p"},{"nodeValue":"2"},{"textContent":"2"},{"tagName":"p"},{"nodeValue":"3"},{"textContent":"3"},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-child Selector #7",
    '<body>

<p>The first paragraph.</p>
<p>The second paragraph.</p>
<p>The third paragraph.</p>
<p>The fourth paragraph.</p>

</body>',
    'p:nth-child(0n+1)',
    '[{"nodeValue":"The first paragraph."},{"textContent":"The first paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-of-type Selector",
    '<body>
    <p>The first paragraph.</p>
    <p>The second paragraph.</p>
    <p>The third paragraph.</p>
    <p>The fourth paragraph.</p>
</body>',
    'p:nth-of-type(2)',
    '[{"nodeValue":"The second paragraph."},{"textContent":"The second paragraph."},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-of-type vs :nth-child #1",
    '<section>
   <h1>Words</h1>
   <p>Little</p>
   <p>Piggy</p>
</section>',
    'p:nth-child(2)',
    '[{"nodeValue":"Little"},{"textContent":"Little"},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-of-type vs :nth-child #2",
    '<section>
   <h1>Words</h1>
   <p>Little</p>
   <p>Piggy</p>
</section>',
    'p:nth-of-type(2)',
    '[{"nodeValue":"Piggy"},{"textContent":"Piggy"},{"tagName":"p"}]'
);

tests_section(
    "CSS :nth-of-type vs :nth-child #3",
    '<section>
   <h1>Words</h1>
   <p>Little</p>
   <p>Piggy</p>
</section>',
    'p:nth-of-type(2), p:nth-child(2)',
    '[{"nodeValue":"Little"},{"textContent":"Little"},{"tagName":"p"},{"nodeValue":"Piggy"},{"textContent":"Piggy"},{"tagName":"p"}]'
);

tests_section(
    "CSS :only-child",
    '<body>
    <div><p>This is a paragraph A.</p></div>
    <div><span>This is a span.</span><p>This is a paragraph B.</p></div>
</body>',
    'p:only-child',
    '[{"nodeValue":"This is a paragraph A."},{"textContent":"This is a paragraph A."},{"tagName":"p"}]'
);

tests_section(
    "CSS :only-of-type",
    '<body>
    <div><p>This is a paragraph A.</p></div>
    <div><p>This is a paragraph B.</p><p>This is a paragraph C.</p></div>
</body>',
    'p:only-of-type',
    '[{"nodeValue":"This is a paragraph A."},{"textContent":"This is a paragraph A."},{"tagName":"p"}]'
);

tests_section(
    "CSS :only-of-type vs :only-child #1",
    '<body>
    <div><p>This is a paragraph A.</p><span>This is paragraph B</span></div>
    <div><p>This is a paragraph C.</p><p>This is a paragraph D.</p></div>
</body>',
    'p:only-of-type',
    '[{"nodeValue":"This is a paragraph A."},{"textContent":"This is a paragraph A."},{"tagName":"p"}]'
);

tests_section(
    "CSS :only-of-type vs :only-child #2",
    '<body>
    <div><p>This is a paragraph A.</p><span>This is paragraph B</span></div>
    <div><p>This is a paragraph C.</p><p>This is a paragraph D.</p></div>
    <div><p>This is a paragraph E.</p></div>
</body>',
    'p:only-of-type:not(:only-child)',
    '[{"nodeValue":"This is a paragraph A."},{"textContent":"This is a paragraph A."},{"tagName":"p"}]'
);

tests_section(
    "CSS :optional",
    '<body>
    <div></div>
    <input value="this" />
    <select><option>this</option></select>
    <textarea>this</textarea>
    
    <input value="not this" required />
    <select required><option>not this</option></select>
    <textarea required>not this</textarea>
</body>',
    ':optional',
    '[{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"value","value":"this"}]},{"nodeValue":"this"},{"textContent":"this"},{"tagName":"select"},{"nodeValue":"this"},{"textContent":"this"},{"tagName":"textarea"}]'
);

tests_section(
    "CSS :required",
    '<body>
    <div></div>
    <input value="this" />
    <select><option>this</option></select>
    <textarea>this</textarea>
    
    <input value="not this" required />
    <select required><option>not this</option></select>
    <textarea required>not this</textarea>
</body>',
    ':required',
    '[{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"value","value":"not this"}]},{"attributes":[{"name":"required","value":""}]},{"nodeValue":"not this"},{"textContent":"not this"},{"tagName":"select"},{"attributes":[{"name":"required","value":""}]},{"nodeValue":"not this"},{"textContent":"not this"},{"tagName":"textarea"},{"attributes":[{"name":"required","value":""}]}]'
);

tests_section(
    "CSS :read-only",
    '<body>
    <input contenteditable value="1" />
    <input contenteditable readonly value="2" />
    <input contenteditable="true" value="3" />
    <input contenteditable="true" readonly value="4" />
    <input contenteditable="false" value="5" />
    <input contenteditable="false" readonly value="6" />
    <input value="7" />
    <input readonly value="8" />
</body>',
    'input:read-only',
    '[{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"contenteditable","value":"false"}]},{"attributes":[{"name":"readonly","value":"readonly"}]},{"attributes":[{"name":"value","value":"6"}]},{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"readonly","value":"readonly"}]},{"attributes":[{"name":"value","value":"8"}]}]'
);

tests_section(
    "CSS :read-write",
    '<body>
    <input contenteditable value="1" />
    <input contenteditable readonly value="2" />
    <input contenteditable="true" value="3" />
    <input contenteditable="true" readonly value="4" />
    <input contenteditable="false" value="5" />
    <input contenteditable="false" readonly value="6" />
    <input value="7" />
    <input readonly value="8" />
    <div>9</div>
    <div contenteditable="true">10</div>
    <div contenteditable="false">11</div>
</body>',
    'input:read-write',
    '[{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"contenteditable","value":""}]},{"attributes":[{"name":"value","value":"1"}]},{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"contenteditable","value":""}]},{"attributes":[{"name":"readonly","value":"readonly"}]},{"attributes":[{"name":"value","value":"2"}]},{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"contenteditable","value":"true"}]},{"attributes":[{"name":"value","value":"3"}]},{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"contenteditable","value":"true"}]},{"attributes":[{"name":"readonly","value":"readonly"}]},{"attributes":[{"name":"value","value":"4"}]},{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"contenteditable","value":"false"}]},{"attributes":[{"name":"value","value":"5"}]},{"nodeValue":""},{"textContent":""},{"tagName":"input"},{"attributes":[{"name":"value","value":"7"}]}]'
);

tests_section(
    "Data-Attribute #1",
    '<body>
    <div data-text="works"></div>
</body>',
    'div[data-text="works"]',
    '[{"nodeValue":""},{"textContent":""},{"tagName":"div"},{"attributes":[{"name":"data-text","value":"works"}]}]'
);

tests_section(
    "Data-Attribute #2",
    '<body>
    <div data-text="works">first</div>
    <div data-text="works">second</div>
</body>',
    'div[data-text="works"]',
    '[{"nodeValue":"first"},{"textContent":"first"},{"tagName":"div"},{"attributes":[{"name":"data-text","value":"works"}]},{"nodeValue":"second"},{"textContent":"second"},{"tagName":"div"},{"attributes":[{"name":"data-text","value":"works"}]}]'
);

tests_section(
    "Data-Attribute #3",
    '<body>
    <div data-text="works">first</div>
    <div data-text="works">second</div>
</body>',
    'div[data-text="works"]:first-of-type',
    '[{"nodeValue":"first"},{"textContent":"first"},{"tagName":"div"},{"attributes":[{"name":"data-text","value":"works"}]}]'
);

tests_section(
    "Multiple pseudo classes",
    '<body>
    <div>Not this1</div>
    <div>this</div>
    <div>Not this2</div>
</body>',
    'div:not(:first-child):not(:last-child)',
    '[{"nodeValue":"first"},{"textContent":"first"},{"tagName":"div"},{"attributes":[{"name":"data-text","value":"works"}]}]'
,true);
?>
</body>
</html>