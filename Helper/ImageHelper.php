<?php

namespace Spod\Sync\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
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

    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        ProductRepository $productRepository,
        SpodLoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->productRepository = $productRepository;
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
        $this->resetOldImages($product);

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
    private function resetOldImages($product): void
    {
        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
        foreach ($existingMediaGalleryEntries as $key => $entry) {
            unset($existingMediaGalleryEntries[$key]);
        }

        $product->setMediaGalleryEntries($existingMediaGalleryEntries);
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
}
