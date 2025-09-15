<?php
date_default_timezone_set('America/New_York');
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60)
        return $diff . " seconds ago";
    elseif ($diff < 3600)
        return floor($diff / 60) . " minutes ago";
    elseif ($diff < 86400)
        return floor($diff / 3600) . " hours ago";
    elseif ($diff < 604800)
        return floor($diff / 86400) . " days ago";
    else
        return date("F j, Y", $timestamp);
}