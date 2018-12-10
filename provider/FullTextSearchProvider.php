<?php
/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

namespace OCA\YourNameSpace\Provider;

use OCA\FullTextSearch\IFullTextSearchProvider;

class MyProvider implements IFullTextSearchProvider {
    const OCSMS_PROVIDER_ID = 'ocsms';

    /** @var IL10N */
	private $l10n;

	/** @var ConfigService */
	private $configService;
    
	/** @var FilesService */
	private $filesService;

	/** @var SearchService */
	private $searchService;

	/** @var MiscService */
	private $miscService;

    public function __construct(IL10N $l10n, ConfigService $configService,
        FilesService $filesService,
        SearchService $searchService, MiscService $miscService
    ) {
        $this->l10n = $l10n;
        $this->configService = $configService;
        $this->filesService = $filesService;
        $this->searchService = $searchService;
        $this->miscService = $miscService;
    }

    /**
     * return unique id of the provider
     */
    public function getId(): string {
        return self::OCSMS_PROVIDER_ID;
    }
};
?>
