<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/**
 * @deprecated Will be removed in version 5. Use PSR-14 event instead.
 *
 * @see \Bitmotion\SecureDownloads\EventListener\SecureDownloadsEventListener
 */
class Signal implements SingletonInterface
{
    protected $extensionConfiguration;

    protected $sdlService;

    protected $environmentService;

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->sdlService = GeneralUtility::makeInstance(SecureDownloadService::class);
        $this->environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
    }

    /**
     * @deprecated Will be removed in version 5. Use PSR-14 event instead.
     */
    public function getPublicUrl(ResourceStorage $storage, AbstractDriver $driver, ResourceInterface $resourceObject, bool $relativeToCurrentScript, array $urlData): void
    {
        if ($driver instanceof LocalDriver && ($resourceObject instanceof File || $resourceObject instanceof ProcessedFile)) {
            try {
                $publicUrl = $driver->getPublicUrl($resourceObject->getIdentifier());
                if ($this->sdlService->pathShouldBeSecured($publicUrl)) {
                    if ($this->environmentService->isEnvironmentInFrontendMode()) {
                        $urlData['publicUrl'] = $this->sdlService->publishResourceUri($publicUrl);
                    } elseif ($this->environmentService->isEnvironmentInBackendMode()) {
                        $urlData['publicUrl'] = '';
                    }
                }
            } catch (Exception $exception) {
                // Do nothing.
            }
        }
    }

    /**
     * @deprecated Will be removed in version 5. Use PSR-14 event instead.
     */
    public function buildIconForResourceSignal(ResourceInterface $resource, string $size, array $options, string $iconIdentifier, ?string $overlayIdentifier): array
    {
        if ($resource instanceof Folder) {
            $publicUrl = $resource->getStorage()->getPublicUrl($resource);
            if ($this->sdlService->folderShouldBeSecured($publicUrl)) {
                $overlayIdentifier = 'overlay-restricted';
            }
        } elseif ($resource instanceof File && empty($resource->getPublicUrl())) {
            $overlayIdentifier = 'overlay-restricted';
        }

        return [$resource, $size, $options, $iconIdentifier, $overlayIdentifier];
    }
}
