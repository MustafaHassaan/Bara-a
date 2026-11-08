<?php

namespace Soflyy\WpAllImport\Vendor\PhpOffice\PhpSpreadsheet\Writer;

use Soflyy\WpAllImport\Vendor\ZipStream\Option\Archive;
use Soflyy\WpAllImport\Vendor\ZipStream\ZipStream;

class ZipStream3
{
    /**
     * @param resource $fileHandle
     */
    public static function newZipStream($fileHandle): ZipStream
    {
        return new ZipStream(
            enableZip64: false,
            outputStream: $fileHandle,
            sendHttpHeaders: false,
            defaultEnableZeroHeader: false,
        );
    }
}
