<?php

namespace App\Services;

/**
 * file system crutches
 */
abstract class Filesystem {

    /**
     * Returns an array of strings from a text file
     * @param string $path Text file path
     * @return array
     */
    static function fileToArray($path) {
        $array = array();
        $array2 = array();
        if (file_exists($path)) {
            $array2 = file($path);
        }
        foreach ($array2 as $value) {
            $value = trim($value);
            if (!$value) {
                continue;
            }

            $array[] = $value;
        }
        return $array;
    }

    /**
     * Returns the path relative to the root directory of the site
     * @param string $path absolute path
     * @return string relative path
     */
    static function getRelPath($path) {
        $is_array = false;
        if (is_array($path)) {
            $is_array = true;
        } else {
            $path = (array) $path;
        }

        $replace = self::unixpath(H . '/');

        foreach ($path as $k => $p) {
            $p = self::unixpath($p);
            $path[$k] = str_replace($replace, '', $p);
        }

        return $is_array ? $path : $path[0];
    }

    /**
     * Returns the optimal chmod on a record
     * @param bool $is_dir для папки
     * @return int
     */
    static function getChmodToWrite($is_dir = false) {
        return 0777;
    }

    /**
     * Returns the optimal read chmod
     * @param bool $is_dir для папки
     * @return int
     */
    static function getChmodToRead($is_dir = false) {
        if ($is_dir) {
            return 0500;
        } else {
            return 0400;
        }
    }

    /**
     * Replaces the directory separator with the specified
     * Removes repeated delimiters
     * @param string $path
     * @param string $sep
     * @return string 
     */
    public static function setPathSeparator($path, $sep = '/') {
        return preg_replace('/[\\\\\/]+/', $sep, $path);
    }

    // get the * UNIX style path
    static function unixpath($path) {
        return str_replace('\\', '/', $path);
    }

    static function systempath($path) {
        return str_replace(array('\\', '/'), IS_WINDOWS ? '\\' : '/', $path);
    }

    /**
     * Creating a directory with setting write permissions
     * @param string $p путь
     * @return boolean
     */
    static function mkdir($p) {
        $p = self::systempath($p);
        if (@mkdir($p, filesystem::getChmodToWrite(true), true)) {
            @chmod($p, filesystem::getChmodToWrite(true));
            return true;
        }
    }

    /**
     * Directory recursive deletion
     * @param string $dir
     * @param boolean $delete_this_dir
     * @return boolean
     */
    static function rmdir($dir, $delete_this_dir = true) {
        $dir = realpath($dir);

        if (!$dir)
            return false;

        $od = opendir($dir);
        while ($rd = readdir($od)) {
            if ($rd == '.' || $rd == '..')
                continue;
            if (is_dir($dir . '/' . $rd)) {
                self::rmdir($dir . '/' . $rd);
            } else {
                chmod($dir . '/' . $rd, filesystem::getChmodToWrite());
                unlink($dir . '/' . $rd);
            }
        }
        closedir($od);


        if ($delete_this_dir) {
            chmod($dir, filesystem::getChmodToWrite(1));
            if (!@rmdir($dir)) {
                // it happens that the first time the folder is not deleted, but we will try again with a second delay
                clearstatcache();
                sleep(1);
                return @rmdir($dir);
            }
            return true;
        } else {
            return true;
        }
    }

    /**
     * Retrieving all folders (recursively)
     * @param string $dir directory path
     * @return array
     */
    static function getAllDirs($dir) {
        $list = array();

        $dir = realpath($dir);
        $od = opendir($dir);
        while ($rd = readdir($od)) {
            if ($rd == '.' || $rd == '..') {
                continue;
            }
            if (is_dir($dir . '/' . $rd)) {
                $list[] = self::unixpath($dir . '/' . $rd);
                $list_n = self::getAllDirs($dir . '/' . $rd);
                foreach ($list_n as $path) {
                    $list[] = $path;
                }
            }
        }
        closedir($od);
        return $list;
    }

    /**
     * Retrieving all files (recursively)
     * @param string $dir directory path
     * @return array
     */
    static function getAllFiles($dir) {
        $list = array();
        $list_n = array();
        $dir = realpath($dir);
        $od = opendir($dir);
        while ($rd = readdir($od)) {
            if ($rd == '.' || $rd == '..') {
                continue;
            }
            if (is_dir($dir . '/' . $rd)) {
                $list_n[] = self::getAllFiles($dir . '/' . $rd);
            } else {
                $list[] = self::unixpath($dir . '/' . $rd);
            }
        }
        closedir($od);

        foreach ($list_n as $lists) {
            foreach ($lists as $path) {
                $list[] = $path;
            }
        }


        return $list;
    }

    /**
     * Returns the total size of all stale temporary files
     * @return int
     */
    static function getOldTmpFilesSize() {
        $files = self::getOldTmpFiles();
        $size = 0;
        foreach ($files as $path) {
            $size += @filesize($path);
        }
        return $size;
    }

    /**
     * Removing stale temporary files
     */
    static function deleteOldTmpFiles() {
        if (@function_exists('set_time_limit')) {
            @set_time_limit(300); // set a limit of 5 minutes
        }


        $yesterday = TIME - 86400;

        $od = opendir(H . '/sys/tmp');
        while ($rd = readdir($od)) {
            if ($rd {0} === '.') {
                // files starting with a skip point
                continue;
            }
            if (filemtime(H . '/sys/tmp/' . $rd) > $yesterday) {
                // the file is not old yet
                continue;
            }
            @unlink(H . '/storage/tmp/' . $rd);
        }
        closedir($od);
    }

    /**
     * Searches for all files in the specified path matching the regular expression
     * @param string $path_abs file name template
     * @param string $pattern
     * @param boolean $recursive search subfolders
     * @return array
     */
    public static function getFilesByPattern($path_abs, $pattern = '/.*/', $recursive = false) {
        $list = array();
        $paths = (array) glob(realpath($path_abs) . '/*');

        foreach ($paths as $path) {
            if (is_file($path) && preg_match($pattern, basename($path)))
                $list[] = self::setPathSeparator($path);
            elseif ($recursive)
                $list = array_merge($list, self::getFilesByPattern($path, $pattern, $recursive));
        }

        return $list;
    }

}