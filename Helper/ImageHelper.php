<?php

namespace Spod\Sync\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Spod\Sync\Api\SpodLoggerInterface;
use GuzzleHttp\Client;

/**
 * Downloads and assignes images to
 * the generated products.
 *
 * @package Spod\Sync\Helper
 */
class ImageHelper
{
    /** @var DirectoryList */
    private $directoryList;

    /** @var Filesystem */
    private $filesystem;

    /** @var ProductRepository */
    private $productRepository;

    /** @var SpodLoggerInterface  */
    private $logger;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ImageHelper constructor.
     *
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param ProductRepository $productRepository
     * @param ResourceConnection $resourceConnection
     * @param SpodLoggerInterface $logger
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        ProductRepository $productRepository,
        ResourceConnection $resourceConnection,
        SpodLoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Assign images to a configurable product.
     *
     * @param $configurableProduct
     * @param $images
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function assignConfigurableImages($configurableProduct, $images)
    {
        $imagePaths = [];
        foreach ($images as $image) {
            $imageUrl = $image->imageUrl;
            $imagePaths[] = $this->downloadImage($configurableProduct->getId(), $imageUrl);
        }

        $this->assignImages($configurableProduct, $imagePaths);
    }

    /**
     * Downloads images and calls a method to handle the assignment.
     *
     * @param $product
     * @param $variantInfo
     * @param $images
     */
    public function downloadAndAssignImages($product, $imageIds, $images)
    {
        try {
            $imagePaths = $this->getImagesForVariant($imageIds, $images);
            $this->assignImages($product, $imagePaths);
            return $product;
        } catch (\Exception $e) {
            $this->logger->logError("image download", $e->getMessage());
        }
    }

    /**
     * Determines which images are to be used for a certain
     * variant of a configurable product. Also triggers the
     * image download and return an array of the url.
     *
     * @param array $imageIds
     * @param array $images
     * @return array
     */
    private function getImagesForVariant($imageIds, $images)
    {
        $urls = [];
        foreach ($imageIds as $imageId) {
            foreach ($images as $imageInfo) {
                if ($imageInfo->id == $imageId) {
                    $urls[$imageId] = $this->downloadImage($imageId, $imageInfo->imageUrl);
                }
            }
        }

        return $urls;
    }

    /**
     * Assigns a given list of images to a product.
     *
     * @param $product
     * @param $imageUrls
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function assignImages(ProductInterface $product, $imageUrls)
    {
        $isMain = true;
        foreach ($imageUrls as $imageId => $imagePath) {
            try {
                $this->addImageToGallery($isMain, $product, $imagePath);
                $isMain = false;
            } catch (\Exception $e) {
                $this->logger->logError("assignImages", $e->getMessage());
            }
        }
    }

    /**
     * Wrapper which adds images to the media gallery of a product
     * and assign labels for main image, thumbnail and small_image.
     *
     * @param bool $firstImage
     * @param ProductInterface $product
     * @param $imagePath
     */
    private function addImageToGallery(bool $isMain, ProductInterface $product, $imagePath): void
    {
        if ($isMain) {
            $product->addImageToMediaGallery($imagePath, ['image', 'small_image', 'thumbnail'], true, false);
        } else {
            $product->addImageToMediaGallery($imagePath, null, true, false);
        }
    }

    /**
     * Generate a local filename and trigger the download
     * of a given image.
     *
     * @param $imageId
     * @param $imageUrl
     * @return false|string
     */
    private function downloadImage($imageId, $imageUrl)
    {
        $dir = sprintf("%s/spod_download", $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath());
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $tmpFile = tempnam($dir, sprintf('spod-image-%s', $imageId));
        $imageFile = $tmpFile . '.png';
        rename($tmpFile, $imageFile);

        $this->fetchImageToFile($imageUrl, $imageFile);

        return $imageFile;
    }

    /**
     * Download an image and save locally.
     *
     * @param $imageUrl
     * @param string $imageFile
     */
    private function fetchImageToFile($imageUrl, string $imageFile): void
    {
        // Fixing sync image error
        $client = new Client();
        $resource = fopen($imageFile, 'wb');
        $stream = \GuzzleHttp\Psr7\stream_for($resource);

        $response = $client->request('GET', $imageUrl, ['save_to' => $stream]);
    }

    /**
     * Remove existing images from products.
     *
     * @param $product
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function resetOldImages($product): void
    {
        $product->setData('media_gallery', ['images' => []]);
        $product->setMediaGalleryEntries([]);
        $product->setImage(false);
        $product->setThumbnail(false);
        $product->setSmallImage(false);

        // workaround for possible M2 bug: while updating products,
        // even after saving the product, sometimes image assignments
        // are still in the database
        $this->deleteGhostImages($product);
    }

    /**
     * Removes assigned images from the database. Only required
     * as a last resort and workaround. In some cases, a few assigned
     * images could not be removed by just setting media gallery entries
     * to an empty array (setMediaGalleryEntries([]).
     *
     * @param ProductInterface $product
     */
    private function deleteGhostImages(ProductInterface $product)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_entity_media_gallery_value_to_entity');
        $sql = sprintf('DELETE FROM %s WHERE entity_id = %d', $table, $product->getId());
        $connection->query($sql);
    }
}
