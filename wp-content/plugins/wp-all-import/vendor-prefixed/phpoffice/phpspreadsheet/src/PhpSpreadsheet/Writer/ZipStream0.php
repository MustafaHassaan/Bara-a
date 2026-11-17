<?php

namespace Soflyy\WpAllImport\Vendor\PhpOffice\PhpSpreadsheet\Writer;

use Soflyy\WpAllImport\Vendor\ZipStream\Option\Archive;
use Soflyy\WpAllImport\Vendor\ZipStream\ZipStream;

class ZipStream0
{
    /**
     * @param resource $fileHandle
     */
    public static function newZipStream($fileHandle): ZipStream
    {
        return class_exists(Archive::class) ? ZipStream2::newZipStream($fileHandle) : ZipStream3::newZipStream($fileHandle);
    }
}
