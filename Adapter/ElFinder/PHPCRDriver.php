<?php

namespace Symfony\Cmf\Bundle\MediaBundle\Adapter\ElFinder;

use FM\ElFinderPHP\Driver\ElFinderVolumeDriver;
use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\SessionInterface;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\File;
use Symfony\Cmf\Bundle\MediaBundle\MediaInterface;

/**
 * @author Sjoerd Peters <sjoerd.peters@gmail.com>
 */
class PHPCRDriver extends ElFinderVolumeDriver
{
    /**
   	 * Driver id
   	 * Must be started from letter and contains [a-z0-9]
   	 * Used as part of volume id
   	 *
   	 * @var string
   	 **/
   	protected $driverId = 'p';

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    protected $dm;

    /**
     * @var SessionInterface $session
     */
    protected $session;

    /**
     * @param DocumentManager $manager
     */
    function __construct(DocumentManager $manager)
    {
        $this->dm = $manager;
        $this->session = $manager->getPhpcrSession();

        $opts = array(
            'workspace'     => '',
            'manager'       => '',
        );
        $this->options = array_merge($this->options, $opts);
    }

    /**
   	 * Return parent directory path
   	 *
   	 * @param  string  $path  file path
   	 * @return string
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _dirname($path) {
   		return dirname($path);
   	}

   	/**
   	 * Return file name
   	 *
   	 * @param  string  $path  file path
   	 * @return string
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _basename($path) {
   		return basename($path);
   	}

   	/**
   	 * Join dir name and file name and retur full path
   	 *
   	 * @param  string  $dir
   	 * @param  string  $name
   	 * @return string
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _joinPath($dir, $name) {
   		return $dir.DIRECTORY_SEPARATOR.$name;
   	}

   	/**
   	 * Return normalized path, this works the same as os.path.normpath() in Python
   	 *
   	 * @param  string  $path  path
   	 * @return string
   	 * @author Troex Nevelin
   	 **/
   	protected function _normpath($path) {
   		if (empty($path)) {
   			return '.';
   		}

   		if (strpos($path, '/') === 0) {
   			$initial_slashes = true;
   		} else {
   			$initial_slashes = false;
   		}

   		if (($initial_slashes)
   		&& (strpos($path, '//') === 0)
   		&& (strpos($path, '///') === false)) {
   			$initial_slashes = 2;
   		}

   		$initial_slashes = (int) $initial_slashes;

   		$comps = explode('/', $path);
   		$new_comps = array();
   		foreach ($comps as $comp) {
   			if (in_array($comp, array('', '.'))) {
   				continue;
   			}

   			if (($comp != '..')
   			|| (!$initial_slashes && !$new_comps)
   			|| ($new_comps && (end($new_comps) == '..'))) {
   				array_push($new_comps, $comp);
   			} elseif ($new_comps) {
   				array_pop($new_comps);
   			}
   		}
   		$comps = $new_comps;
   		$path = implode('/', $comps);
   		if ($initial_slashes) {
   			$path = str_repeat('/', $initial_slashes) . $path;
   		}

   		return $path ? $path : '.';
   	}

   	/**
   	 * Return file path related to root dir
   	 *
   	 * @param  string  $path  file path
   	 * @return string
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _relpath($path) {
   		return $path == $this->root ? '' : substr($path, strlen($this->root)+1);
   	}

   	/**
   	 * Convert path related to root dir into real path
   	 *
   	 * @param  string  $path  file path
   	 * @return string
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _abspath($path) {
   		return $path == DIRECTORY_SEPARATOR ? $this->root : $this->root.DIRECTORY_SEPARATOR.$path;
   	}

   	/**
   	 * Return fake path started from root dir
   	 *
   	 * @param  string  $path  file path
   	 * @return string
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _path($path) {
   		return $this->rootName.($path == $this->root ? '' : $this->separator.$this->_relpath($path));
   	}

   	/**
   	 * Return true if $path is children of $parent
   	 *
   	 * @param  string  $path    path to check
   	 * @param  string  $parent  parent path
   	 * @return bool
   	 * @author Dmitry (dio) Levashov
   	 **/
   	protected function _inpath($path, $parent) {
   		return $path == $parent || strpos($path, $parent.DIRECTORY_SEPARATOR) === 0;
   	}

    /**
     * Return stat for given path.
     * Stat contains following fields:
     * - (int)    size    file size in b. required
     * - (int)    ts      file modification time in unix time. required
     * - (string) mime    mimetype. required for folders, others - optionally
     * - (bool)   read    read permissions. required
     * - (bool)   write   write permissions. required
     * - (bool)   locked  is object locked. optionally
     * - (bool)   hidden  is object hidden. optionally
     * - (string) alias   for symlinks - link target path relative to root path. optionally
     * - (string) target  for symlinks - link target path. optionally
     *
     * If file does not exists - returns empty array or false.
     *
     * @param  string $path    file path
     * @return array|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _stat($path)
    {
        /** @var File $doc */
        $doc = $this->dm->find(null, $path);

        if(!$doc || !$doc instanceof File){
            return false;
        }

        $stat = array(
            'size' => $doc->getSize(),
            'ts' => $doc->getUpdatedAt()->getTimestamp(),
            'mime' => $doc->getContentType(),
            'read' => true,
            'write' => true,
            'locked' => false,
            'hidden' => false,
        );
        return $stat;
    }

    /**
     * Return true if path is dir and has at least one childs directory
     *
     * @param  string $path  dir path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _subdirs($path)
    {
        $doc = $this->dm->find(null, $path);
        if($doc && $subs = $this->dm->getChildren($doc)){
            return count($subs);
        }
        return false;
    }

    /**
     * Return object width and height
     * Ususaly used for images, but can be realize for video etc...
     *
     * @param  string $path  file path
     * @param  string $mime  file mime type
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _dimensions($path, $mime)
    {
        // TODO: Implement _dimensions() method.
    }

    /**
     * Return files list in directory
     *
     * @param  string $path  dir path
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function _scandir($path)
    {
        $doc = $this->dm->find(null, $path);
        return $this->dm->getChildren($doc) ?: array();
    }

    /**
     * Open file and return file pointer
     *
     * @param  string $path  file path
     * @param  bool $write open file for writing
     * @return resource|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _fopen($path, $mode = "rb")
    {
        // TODO: Implement _fopen() method.
    }

    /**
     * Close opened file
     *
     * @param  resource $fp    file pointer
     * @param  string $path  file path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _fclose($fp, $path = '')
    {
        // TODO: Implement _fclose() method.
    }

    /**
     * Create dir and return created dir path or false on failed
     *
     * @param  string $path  parent dir path
     * @param string $name  new directory name
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _mkdir($path, $name)
    {
        return $this->_mkfile($path, $name);
    }

    /**
     * Create file and return it's path or false on failed
     *
     * @param  string $path  parent dir path
     * @param string $name  new file name
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _mkfile($path, $name)
    {
        if($this->dm->find(null, $filename = $this->_joinPath($path, $name))){
            return false;
        }

        $file = new File();
        $file->setName($name);
        $file->setId($filename);
        $this->dm->persist($file);
        $this->dm->flush($file);
        return $filename;
    }

    /**
     * Create symlink
     *
     * @param  string $source     file to link to
     * @param  string $targetDir  folder to create link in
     * @param  string $name       symlink name
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _symlink($source, $targetDir, $name)
    {
        // TODO: Implement _symlink() method.
    }

    /**
     * Copy file into another file (only inside one volume)
     *
     * @param  string $source  source file path
     * @param  string $targetDir  target dir path
     * @param  string $name    file name
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _copy($source, $targetDir, $name)
    {
        if($this->dm->find(null, $targetPath = $this->_joinPath($targetDir, $name))){
            return false;
        }

        $doc = $this->dm->find(null, $source);
        $copy = clone($doc);
        $copy->setPath($targetPath);
        $this->dm->persist($copy);
        $this->dm->flush();
        return true;
    }

    /**
     * Move file into another parent dir.
     * Return new file path or false.
     *
     * @param  string $source  source file path
     * @param  string $targetDir  target dir path
     * @param  string $name    file name
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _move($source, $targetDir, $name)
    {
        try {
            $doc = $this->dm->find(null, $source);
            $this->dm->move($doc, $this->_joinPath($targetDir, $name));
            $this->dm->flush();
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Remove file
     *
     * @param  string $path  file path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _unlink($path)
    {
        try {
            $doc = $this->dm->find(null, $path);
            $this->dm->remove($doc);
            $this->dm->flush();
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Remove dir
     *
     * @param  string $path  dir path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _rmdir($path)
    {
        if($doc = $this->dm->find(null, $path)){
            try {
                $this->dm->remove($doc);
                $this->dm->flush($doc);
                return true;
            } catch(\Exception $e){
            }
        }
        return false;
    }

    /**
     * Create new file and write into it from file pointer.
     * Return new file path or false on error.
     *
     * @param  resource $fp   file pointer
     * @param  string $dir  target dir path
     * @param  string $name file name
     * @param  array $stat file stat (required by some virtual fs)
     * @return bool|string
     * @author Dmitry (dio) Levashov
     **/
    protected function _save($fp, $dir, $name, $stat)
    {
        // TODO: Implement _save() method.
    }

    /**
     * Get file contents
     *
     * @param  string $path  file path
     * @return string|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _getContents($path)
    {
        /** @var File $doc */
        $doc = $this->dm->find(null, $path);

        if(!$doc || !$doc instanceof File){
            return false;
        }
        return $doc->getContentAsStream();
    }

    /**
     * Write a string to a file
     *
     * @param  string $path     file path
     * @param  string $content  new file content
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _filePutContents($path, $content)
    {
        // @TODO new file
        /** @var File $doc */
        $doc = $this->dm->find(null, $path);

        if(!$doc || !$doc instanceof File){
            return false;
        }
        return $doc->setContentFromString($content);
    }

    /**
     * Extract files from archive
     *
     * @param  string $path file path
     * @param  array $arc  archiver options
     * @return bool
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     **/
    protected function _extract($path, $arc)
    {
        // TODO: Implement _extract() method.
    }

    /**
     * Create archive and return its path
     *
     * @param  string $dir    target dir
     * @param  array $files  files names list
     * @param  string $name   archive name
     * @param  array $arc    archiver options
     * @return string|bool
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     **/
    protected function _archive($dir, $files, $name, $arc)
    {
        // TODO: Implement _archive() method.
    }

    /**
     * Detect available archivers
     *
     * @return void
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     **/
    protected function _checkArchivers()
    {
        // TODO: Implement _checkArchivers() method.
    }


}
