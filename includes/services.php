<?php

function isApacheRunning()
{
    $output = shell_exec("ps aux | grep httpd | grep lampp | grep -v grep");

    return !empty($output);
}

function isMariaDBRunning()
{
    $output = shell_exec("ps aux | grep lampp | grep mysql | grep -v grep");

    return !empty($output);
}