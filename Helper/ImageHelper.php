<?php

namespace Spod\Sync\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Spod\Sync\Api\SpodLoggerInterface;

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

    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        ProductRepository $productRepository,
        ResourceConnection $resourceConnection,
        SpodLoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
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
            $this->logger->logError($e->getMessage());
        }
    }

    /**
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
                $this->logger->logError($e->getMessage());
            }
        }
    }

    /**
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
     * @param $imageUrl
     * @param string $imageFile
     */
    private function fetchImageToFile($imageUrl, string $imageFile): void
    {
        $ch = curl_init($imageUrl);
        $fp = fopen($imageFile, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
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
