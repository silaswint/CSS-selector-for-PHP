<?php
include __DIR__ . "/jQuery.php";

$query =
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
        ")
        ->_("ul li:nth-of-type(odd)");
?>

<h2>Selected nodes</h2>
<ul>
<?php
/*
    - this
    - is
    - awesome
 */
foreach ($query as $element) {
    echo "<li>" . $element->nodeValue . "</li>";
}
?>
</ul>