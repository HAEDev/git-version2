<?php

namespace GitVersion;

class GitVersion {

	/**
     * @param string $basePath The base path of the application where .git folder is in
     * @return string
     */
    public static function version($basePath)
    {
        $version = '';

        $head = self::makePath($basePath, '.git/HEAD');
        if (is_file($head)) {
            $branch = file_get_contents($head);

            if (preg_match('/^[a-f0-9]+$/', $branch)) {
                // may be a specific commit checkout
                $version = substr($branch, 0, 7);

                // tag (must run git pack-refs)
                $packedRefs = self::makePath($basePath, '.git/packed-refs');
                if (is_file($packedRefs)) {
                    $handle = fopen($packedRefs, 'r');
                    if ($handle) {
                        $tag = '';
                        while (($line = fgets($handle)) !== false) {
                            if (preg_match('@^([a-f0-9]+)\s+refs/tags/(.+)$@', $line, $matches)) {
                                $hash = $matches[1];
                                $tag = $matches[2];

                                if (trim($hash) == trim($branch)) {
                                    $version = $tag;
                                    break;
                                }
                            } elseif (preg_match('@^\^([a-f0-9]+)$@', $line, $matches)) {
                                if (trim($matches[1]) == trim($branch) && $tag !== '') {
                                    $version = $tag;
                                    break;
                                }
                            }
                        }
                        fclose($handle);
                    }
                }
            } elseif (preg_match('/^ref:(.+)$/', $branch, $matches)) {
                // branch
                $version = basename(trim($matches[1]));
                $branchPath = self::makePath($basePath, '.git/' . trim($matches[1]));
                if (is_file($branchPath)) {
                    $version .= '-' . substr(file_get_contents($branchPath), 0, 7);
                }
            }
        }

        return $version;
    }

	/**
	 * @param string $basePath
	 * @param string $path
	 * @return string
	 */
	private static function makePath($basePath, $path)
	{
		return preg_replace('@[/\\\]+$@', '', $basePath) . '/' . preg_replace('@^[/\\\]+@', '', $path);
	}
}