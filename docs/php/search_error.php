<?php
$dir = dirname(dirname(__DIR__)); // Go up to dbms root
$search = "失败";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, $search) !== false) {
            echo "Found in: " . $file->getPathname() . "\n";
        }
    }
}
echo "Search complete.\n";
?>