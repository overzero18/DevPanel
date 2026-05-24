<?php

$target = __DIR__ . '/../screenshots/current/devpanel-demo.gif';
$width = 960;
$height = 540;
$image = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($image, 15, 23, 42);
$panel = imagecolorallocate($image, 30, 41, 59);
$accent = imagecolorallocate($image, 79, 158, 249);
$green = imagecolorallocate($image, 16, 185, 129);
$text = imagecolorallocate($image, 226, 232, 240);
$muted = imagecolorallocate($image, 148, 163, 184);

imagefilledrectangle($image, 0, 0, $width, $height, $bg);
imagefilledrectangle($image, 24, 24, 220, 516, $panel);
imagefilledrectangle($image, 244, 24, 936, 516, $panel);
imagefilledrectangle($image, 264, 58, 430, 114, $accent);
imagefilledrectangle($image, 452, 58, 618, 114, $green);
imagefilledrectangle($image, 640, 58, 806, 114, imagecolorallocate($image, 245, 158, 11));

imagestring($image, 5, 52, 58, 'DevPanel', $text);
imagestring($image, 3, 52, 96, 'Dashboard', $muted);
imagestring($image, 3, 52, 126, 'Projects', $muted);
imagestring($image, 3, 52, 156, 'File Manager', $muted);
imagestring($image, 3, 52, 186, 'Changelog', $muted);
imagestring($image, 5, 284, 78, 'Apache OK', $text);
imagestring($image, 5, 472, 78, 'Docker OK', $text);
imagestring($image, 5, 660, 78, 'Backups OK', $text);
imagestring($image, 5, 284, 150, 'Local XAMPP control panel', $text);
imagestring($image, 3, 284, 190, 'Projects, MariaDB, logs, terminal, Docker and backups.', $muted);
imagestring($image, 3, 284, 226, 'Generated preview GIF for README.', $muted);

imagegif($image, $target);
imagedestroy($image);
echo "GIF generado en $target\n";
