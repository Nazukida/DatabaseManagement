<?php
function getQueryTime($start) {
    return number_format(microtime(true) - $start, 4);
}
?>