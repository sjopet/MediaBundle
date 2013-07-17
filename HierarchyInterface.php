<?php

namespace Symfony\Cmf\Bundle\MediaBundle;

/**
 * Interface for file objects containing directories.
 *
 * The path to a file is: /path/to/file/filename.ext
 *
 * For PHPCR the id is being the path.
 * For ORM the file path can concatenate the directory identifiers with '/'
 * and ends with the file identifier. For a nice path a slug could be used
 * as identifier.
 *
 * For ORM you could use:
 * - https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/tree.md
 * - https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/sluggable.md
 *
 * This is to be kept compatible with the Gaufrette adapter to be able to use a
 * filesystem with directories.
 */
interface HierarchyInterface extends MediaInterface
{
    /**
     * Get full path: /path/of/directory | /path/to/file/filename.ext
     *
     * @return string
     */
    public function getPath();

    /**
     * Get the parent node.
     *
     * @return DirectoryInterface|null
     */
    public function getParent();

    /**
     * Set the parent node.
     *
     * @param HierarchyInterface $parent
     *
     * @return boolean
     */
    public function setParent(HierarchyInterface $parent);

    /**
     * Check if the directory is readable
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Check if the directory is writable
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Get the file size in bytes
     *
     * @return integer
     */
    public function getSize();
}
