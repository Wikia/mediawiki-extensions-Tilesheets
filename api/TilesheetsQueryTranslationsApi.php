<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\ILoadBalancer;

class TilesheetsQueryTranslationsApi extends ApiQueryBase {
	public function __construct(
		$query,
		$moduleName,
		private ILoadBalancer $loadBalancer
	) {
		parent::__construct( $query, $moduleName, 'ts' );
	}

	public function getAllowedParams() {
		return [
			'id' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
				IntegerDef::PARAM_MIN => 1,
			],
			'lang' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
			],
		];
	}

	public function getExamples() {
		return [
			'api.php?action=query&list=tiletranslations&tsid=6',
			'api.php?action=query&list=tiletranslations&tslang=es',
			'api.php?action=query&list=tiletranslations&tsid=6&tslang=es',
		];
	}

	public function execute() {
		$id = $this->getParameter( 'id' );
		$lang = $this->getParameter( 'lang' );

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$results = $dbr->select(
			'ext_tilesheet_languages',
			'*',
			[
				'entry_id' => $id,
				"lang = {$dbr->addQuotes($lang)} OR {$dbr->addQuotes($lang)} = ''",
			]
		);

		$ret = [];

		foreach ( $results as $res ) {
			$ret[] = [
				'entry_id' => $res->entry_id,
				'description' => $res->description,
				'display_name' => $res->display_name,
				'language' => $res->lang,
			];
		}

		$this->getResult()->addValue( 'query', 'tiles', $ret );
	}
}
