<?php

namespace Hmaus\Spas\Filesystem;

class InputFinder
{
    public function getContents($inputPath)
    {
        return file_get_contents($inputPath);
    }
}
