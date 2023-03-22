<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use Wikimedia\ParamValidator\ParamValidator;

class TilesheetsAddSheetApi extends ApiBase {
	public function __construct(
		$query,
		$moduleName,
		private PermissionManager $permissionManager
	) {
		parent::__construct( $query, $moduleName, 'ts' );
	}

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'mod' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
            ),
            'sizes' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '16|32',
				ParamValidator::PARAM_ISMULTI => true,
            ),
        );
    }

    public function needsToken() {
        return 'csrf';
    }

    public function getTokenSalt() {
        return '';
    }

    public function mustBePosted() {
        return true;
    }

    public function isWriteMode() {
        return true;
    }

    public function getExamples() {
        return array(
            'api.php?action=createsheet&tssummary=This mod rocks&tsmod=MOD&tssizes=16|32|64',
        );
    }

    public function execute() {
		if ( !$this->permissionManager->userHasRight( $this->getUser(), 'edittilesheets' ) ) {
            $this->dieWithError('You do not have permission to create tilesheets', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $sizes = $this->getParameter('sizes');
        $sizes = implode(',', $sizes);
        $summary = $this->getParameter('summary');

        $result = SheetManager::createSheet($mod, $sizes, $this->getUser(), $summary);
        $this->getResult()->addValue('edit', 'createsheet', array($mod => $result));
    }
}
