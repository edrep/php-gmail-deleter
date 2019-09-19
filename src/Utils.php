<?php


namespace Edrep;


class Utils
{

    public static function confirmOrAbort($message): void
    {
        print("\n" . $message);

        $handle = fopen('php://stdin', 'rb');
        $line   = fgets($handle);
        fclose($handle);

        if (trim($line) !== 'yes') {
            echo "ABORTING!\n";
            exit;
        }
    }
}