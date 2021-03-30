<?php
function Only_child_First() {
    return "count(preceding-sibling::*)+count(following-sibling::*)=0";
}