<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

class TilesheetsDeleteTilesApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'ids' => array(
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => true,
				IntegerDef::PARAM_MIN => 1,
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
            'api.php?action=deletetiles&tsids=1|2|3',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieWithError('You do not have permission to delete tiles', 'permissiondenied');
        }

        $ids = $this->getParameter('ids');
        $summary = $this->getParameter('summary');

        $ret = array();
        foreach ($ids as $id) {
            $result = TileManager::deleteEntry($id, $this->getUser(), $summary);
            $ret[$id] = $result;
        }

        $this->getResult()->addValue('edit', 'deletetiles', $ret);
    }
}
