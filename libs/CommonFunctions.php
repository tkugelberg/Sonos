<?php

trait CommonFunctions
{

  private function getPlaylistReplacementFrom()
  {
    return  array(
      '/\.m3u$/',
      '/\.M3U$/',
      '/_/'
    );
  }

  private function getPlaylistReplacementTo()
  {
    return  array(
      '',
      '',
      ' '
    );
  }
}
