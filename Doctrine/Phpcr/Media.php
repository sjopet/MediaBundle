<?php

namespace Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr;

use Symfony\Cmf\Bundle\MediaBundle\Model\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * @var object
     */
    protected $parent;

    /**
     * @var string
     */
    protected $createdBy;

    /**
     * @var string
     */
    protected $updatedBy;

    /**
     * @param object $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return object|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get full file path: /path/to/file/filename.ext
     *
     * @return string
     */
    public function getPath()
    {
        // TODO: Implement getPath() method.
    }

    /**
     * The mime type of this media element
     *
     * @return string
     */
    public function getContentType()
    {
        // TODO: Implement getContentType() method.
    }

    /**
     * Getter for createdBy
     * The createdBy is assigned by the content repository
     * This is the name of the (jcr) user that created the node
     *
     * @return string name of the (jcr) user who created the file
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Getter for updatedBy
     * The createdBy is assigned by the content repository
     * This is the name of the (jcr) user that updated the node
     *
     * @return string name of the (jcr) user who updated the file
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
