<?php

function formatProjectModifiedAt($timestamp)
{
    if (!$timestamp)
    {
        return 'Sin fecha';
    }

    return date('d/m/Y H:i', $timestamp);
}
