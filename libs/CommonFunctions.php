<?php

declare(strict_types=1);

trait CommonFunctions
{
    private function getPlaylistReplacementFrom()
    {
        return  [
            '/\.m3u$/',
            '/\.M3U$/',
            '/_/'
        ];
    }

    private function getPlaylistReplacementTo()
    {
        return  [
            '',
            '',
            ' '
        ];
    }
}
