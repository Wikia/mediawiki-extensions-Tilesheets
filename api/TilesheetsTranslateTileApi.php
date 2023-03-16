<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class TilesheetsTranslateTileApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'id' => array(
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
				IntegerDef::PARAM_MIN => 1,
            ),
            'lang' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
            ),
            'name' => array(
				ParamValidator::PARAM_TYPE => 'string',
            ),
            'description' => array(
				ParamValidator::PARAM_TYPE => 'string',
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
            'api.php?action=translatetile&tsid=6&tslang=es-ni&tsname=Esmeralda',
        );
    }

    public function execute() {
		if (!MediaWikiServices::getInstance()->getPermissionManager()->userHasRight(
			$this->getUser(), 'translatetiles' ) ) {
            $this->dieWithError('You do not have permission to add tiles', 'permissiondenied');
        }

        $id = $this->getParameter('id');
        $lang = $this->getParameter('lang');
        $name = $this->getParameter('name');
        $desc = $this->getParameter('description');

        if (empty($name) && empty($desc)) {
            $this->dieWithError('You have to specify one of name or description', 'nochangeparam');
        }

        $dbr = wfGetDB(DB_REPLICA);
        $stuff = $dbr->select('ext_tilesheet_languages', '*', array('entry_id' => $id, 'lang' => $lang));

        TileTranslator::updateTable($id, $name, $desc, $lang, $this->getUser());
        $ret = array(
            'entry_id' => $id,
            'language' => $lang,
            'display_name' => $name,
            'description' => $desc,
        );
        if ($stuff->numRows() == 0) {
            $this->getResult()->addValue('edit', 'newtranslation', $ret);
        } else {
            $this->getResult()->addValue('edit', 'translatetile', $ret);
        }
    }
}