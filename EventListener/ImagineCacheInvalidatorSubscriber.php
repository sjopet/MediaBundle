<?php

namespace Symfony\Cmf\Bundle\MediaBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Cmf\Bundle\MediaBundle\ImageInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * A listener to invalidate the imagine cache when Image documents are modified
 */
class ImagineCacheInvalidatorSubscriber implements EventSubscriber
{
    /**
     * @var CacheManager
     */
    private $manager;

    /**
     * Used to get the request from to remove cache
     * @var Container
     */
    private $container;

    /**
     * Filter names to invalidate
     * @var array
     */
    private $filters;

    /**
     * @param CacheManager $manager   the imagine cache manager
     * @param Container    $container to get the request from. Need to inject
     *      this as otherwise we have a scope problem
     * @param array        $filter    list of filter names to invalidate
     */
    public function __construct(CacheManager $manager, Container $container, $filters)
    {
        $this->manager = $manager;
        $this->container = $container;
        $this->filters = $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'postUpdate',
            'preRemove',
        );
    }

    /**
     * Invalidate cache after a document was updated.
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->invalidateCache($args);
    }

    /**
     * Invalidate the cache when removing an image. Do this before the flush to
     * still have access to the parent of the document.
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->invalidateCache($args);
    }

    /**
     * Check if this could mean an image document was modified (check resource,
     * file and image)
     *
     * @param LifecycleEventArgs $args
     */
    private function invalidateCache(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        // TODO: do we still need this?
//        if ($document instanceof Resource) {
//            $document = $document->getParent();
//        }
//        if ($document instanceof File) {
//            $document = $document->getParent();
//        }
        if ($object instanceof ImageInterface) {
            if (! $this->container->isScopeActive('request')
                || ! $request = $this->container->get('request')
            ) {
                // do not fail on CLI
                return;
            }
            foreach ($this->filters as $filter) {
                $path = $this->manager->resolve($request, $this->getFilePath($object), $filter);
                if ($path instanceof RedirectResponse) {
                    $path = $path->getTargetUrl();
                }

                // TODO: this might not be needed https://github.com/liip/LiipImagineBundle/issues/162
                if (false !== strpos($path, $filter)) {
                    $path = substr($path, strpos($path, $filter) + strlen($filter));
                }
                $this->manager->remove($path, $filter);
            }
        }
    }

    /**
     * Get full file path: /path/to/file/filename.ext
     *
     * For PHPCR the id is being the path.
     * For ORM the file path can concatenate the directory identifiers with '/'
     * and ends with the file identifier. For a nice path a slug could be used
     * as identifier.
     *
     * @return string
     */
    protected function getFilePath(FileInterface $file)
    {
        if ($file instanceof DirectoryInterface) {
            $path = $file->getPath();
        } else {
            $path = $file->getId();
        }

        return $path;
    }
}
